<!--
  - @copyright Copyright (c) 2021. Fabian Kirchesch <fabian.kirchesch@csoc.de>
  -
  - @author Fabian Kirchesch <fabian.kirchesch@csoc.de>
  -->

<!--
  - View to display and add Requests
  -->
<template>
	<div class="tab_content">
		<v-btn
			v-if="isAnalyst || isAdmin"
			color="light-blue"
			@click="openDialog()">
			{{ t('shifts','New Request') }}
		</v-btn>
		<RequestsModal v-if="dialogOpen"
			:is-admin="isAdmin"
			:shifts="shifts"
			:analysts="analysts"
			@close="closeDialog"
			@saved="dialogSaved" />
		<h1>{{ t('shifts','In Progress') }}</h1>
		<!--eslint-disable-->
		<v-list>
			<v-list-group
				v-for="shiftsChange in inProgressShiftsChanges"
				:key="shiftsChange.id"
				v-model="shiftsChange.active"
				:prepend-icon=" shiftsChange.type === '0' ? 'mdi-swap-horizontal' : 'icon-confirm'">
				<template v-slot:activator>
					<v-list-item-content>
						<v-list-item-title v-text="shiftsChange.type === '0' ? t('shifts','Swap') : t('shifts','Offer')"></v-list-item-title>
						<v-row>
							<v-col
								cols="12"
								em="6"
								class="display_name_col">
								<v-list-item-subtitle v-text="getDisplayNameByUserId(shiftsChange.oldAnalystId)"></v-list-item-subtitle>
							</v-col>
							<v-col
								cols="12"
								em="6"
								class="shifts_details_col">
								<v-list-item-subtitle v-text="getShiftsDetailsByShiftsIdAsString(shiftsChange.oldShiftsId)"></v-list-item-subtitle>
							</v-col>
						</v-row>
						<v-row>
							<v-col
								cols="12"
								em="6"
								class="display_name_col">
								<v-list-item-subtitle v-text="getDisplayNameByUserId(shiftsChange.newAnalystId)"></v-list-item-subtitle>
							</v-col>
							<v-col
								cols="12"
								em="6"
								class="shifts_details_col">
								<v-list-item-subtitle v-text="getShiftsDetailsByShiftsIdAsString(shiftsChange.newShiftsId)"></v-list-item-subtitle>
							</v-col>
						</v-row>
					</v-list-item-content>
				</template>
				<div class="float_right list_items">
					<p v-if="isAdmin && shiftsChange.analystApproval === '1'">
						{{ t('shifts', 'Analyst Approval: ')
					+ (shiftsChange.analystApproval === '1' ? 'true' : 'false') + t('shifts', ' at ')
					+ getDateString(shiftsChange.analystApprovalDate) }}
					</p>
					<v-btn
						v-if="(shiftsChange.newAnalystId === currentUser && shiftsChange.analystApproval !== '1') || (isAdmin && shiftsChange.analystApproval === '1') || (isAdmin && shiftsChange.newAnalystId === currentUser)"
						color="red"
						@click="disapproved(shiftsChange)">
						{{ t('shifts','Decline') }}
						<v-icon>
							icon-close
						</v-icon>
					</v-btn>
					<v-btn
						v-if="(shiftsChange.newAnalystId === currentUser && shiftsChange.analystApproval !== '1') || (isAdmin && shiftsChange.analystApproval === '1') || (isAdmin && shiftsChange.newAnalystId === currentUser)"
						color="green"
						@click="approved(shiftsChange)">
						{{ t('shifts','Approve') }}
						<v-icon>
							icon-checkmark
						</v-icon>
					</v-btn>
					<v-btn
						color="red lighten-1"
						dark
						@click="deleteRequest(shiftsChange)">
						{{ t('shifts', 'Delete') }}
					</v-btn>
				</div>
			</v-list-group>
		</v-list>
		<h1>{{ t('shifts','Processed') }}</h1>
		<v-list>
			<v-list-group
				v-for="shiftsChange in doneShiftsChanges"
				:key="shiftsChange.id"
				v-model="shiftsChange.active"
				:prepend-icon=" shiftsChange.type === '0' ? 'mdi-swap-horizontal' : 'icon-confirm'">
				<template v-slot:activator>
					<v-list-item-content>
						<v-list-item-title v-text="shiftsChange.type === '0' ? t('shifts','Swap') : t('shifts','Offer')"></v-list-item-title>
						<v-row>
							<v-col
								cols="12"
								em="6"
								class="display_name_col">
								<v-list-item-subtitle v-text="getDisplayNameByUserId(shiftsChange.oldAnalystId)"></v-list-item-subtitle>
							</v-col>
							<v-col
								cols="12"
								em="6"
								class="shifts_details_col">
								<v-list-item-subtitle v-text="getShiftsDetailsByShiftsIdAsString(shiftsChange.oldShiftsId)"></v-list-item-subtitle>
							</v-col>
						</v-row>
						<v-row>
							<v-col
								cols="12"
								em="6"
								class="display_name_col">
								<v-list-item-subtitle v-text="getDisplayNameByUserId(shiftsChange.newAnalystId)"></v-list-item-subtitle>
							</v-col>
							<v-col
								cols="12"
								em="6"
								class="shifts_details_col">
								<v-list-item-subtitle v-text="getShiftsDetailsByShiftsIdAsString(shiftsChange.newShiftsId)"></v-list-item-subtitle>
							</v-col>
						</v-row>
					</v-list-item-content>
				</template>
				<div class="done_shifts_items list_items">
					<p>
						{{ t('shifts', 'Analyst Approval: ')
					+ (shiftsChange.analystApproval === '1' ? 'true' : 'false') + t('shifts', ' at ')
					+ getDateString(shiftsChange.analystApprovalDate) }}
					</p>
					<p>
						{{ t('shifts', 'Admin Approval: ')
					+ (shiftsChange.adminApproval === '1' ? 'true' : 'false') + t('shifts', ' at ')
					+ getDateString(shiftsChange.adminApprovalDate) }}
					</p>
				</div>
			</v-list-group>
		</v-list>
		<!--eslint-enable-->
	</div>
</template>

<script>
import RequestsModal from '../components/Modal/RequestsModal'
import { showError, showWarning } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { updateExistingCalendarObjectFromShiftsChange } from '../services/calendarService'
import { mapGetters } from 'vuex'
import dayjs from 'dayjs'
export default {
	name: 'Requests',
	components: {
		RequestsModal,
	},
	data() {
		return {
			dialogOpen: false,
		}
	},
	computed: {
		...mapGetters({
			shiftsChanges: 'allShiftsChanges',
			analysts: 'allAnalysts',
			shiftsTypes: 'allShiftsTypes',
			shifts: 'allShifts',
			isAdmin: 'isAdmin',
			currentUser: 'currentUserId',
		}),
		// returns ShiftsChanges which are still in Progress and needs approval
		inProgressShiftsChanges() {
			return this.shiftsChanges.filter((shiftsChange) => {
				if (shiftsChange.oldShift === undefined || shiftsChange.newShift === undefined) {
					shiftsChange.oldShift = this.allShifts.find((shift) => shift.id.toString() === shiftsChange.oldShiftsId.toString())
					shiftsChange.newShift = this.allShifts.find((shift) => shift.id.toString() === shiftsChange.newShiftsId.toString())
				}
				const oldDate = dayjs(shiftsChange.oldShift.date)
				const newDate = dayjs(shiftsChange.newShift.date)
				return !(shiftsChange.adminApprovalDate !== '' && shiftsChange.analystApprovalDate !== '')
						&& dayjs().subtract(14, 'day').isBefore(oldDate)
						&& dayjs().subtract(14, 'day').isBefore(newDate)
			})
		},
		// returns ShiftsChanges which dont need further action
		doneShiftsChanges() {
			return this.shiftsChanges.filter((shiftsChange) => {
				const oldDate = dayjs(shiftsChange.oldShift.date)
				const newDate = dayjs(shiftsChange.newShift.date)
				return shiftsChange.adminApprovalDate !== '' && shiftsChange.analystApprovalDate !== ''
					&& dayjs().subtract(14, 'day').isBefore(oldDate)
					&& dayjs().subtract(14, 'day').isBefore(newDate)
			})
		},
		// returns if current user is an analyst
		isAnalyst() {
			let found = false
			for (let i = 0; i < this.analysts.length; i++) {
				if (this.analysts[i].uid === this.currentUser) {
					found = true
					break
				}
			}
			return found
		},
	},
	methods: {
		openDialog() {
			this.dialogOpen = true
		},
		closeDialog() {
			this.dialogOpen = false
		},
		dialogSaved(shiftsChanges) {
			this.dialogOpen = false
			this.shiftsChanges.push(...shiftsChanges)
		},
		getDisplayNameByUserId(userId) {
			return this.analysts.find((analyst) => {
				return analyst.uid === userId
			}).name
		},
		async disapproved(shiftsChange) {
			if (this.isAdmin && shiftsChange.newAnalystId === this.currentUser) {
				shiftsChange.adminApproval = '0'
				shiftsChange.adminApprovalDate = new Date()
				shiftsChange.analystApproval = '0'
				shiftsChange.analystApprovalDate = new Date()
			} else if (this.isAdmin) {
				shiftsChange.adminApproval = '0'
				shiftsChange.adminApprovalDate = new Date()
			} else {
				shiftsChange.analystApproval = '0'
				shiftsChange.analystApprovalDate = new Date()
			}
			await this.saveShiftsChange(shiftsChange)
		},
		async approved(shiftsChange) {
			if (this.isAdmin && shiftsChange.newAnalystId === this.currentUser) {
				shiftsChange.adminApproval = '1'
				shiftsChange.adminApprovalDate = new Date()
				shiftsChange.analystApproval = '1'
				shiftsChange.analystApprovalDate = new Date()
			} else if (this.isAdmin) {
				shiftsChange.adminApproval = '1'
				shiftsChange.adminApprovalDate = new Date()
			} else {
				shiftsChange.analystApproval = '1'
				shiftsChange.analystApprovalDate = new Date()
			}
			await this.saveShiftsChange(shiftsChange)
		},
		async saveShiftsChange(shiftsChange) {
			try {
				// checks for approval to change shifts
				// only done when both approvals are given
				if (shiftsChange.adminApproval === '1' && shiftsChange.analystApproval === '1') {
					const oldAnalyst = this.analysts.find((analyst) => {
						return analyst.uid === shiftsChange.oldAnalystId
					})
					const newAnalyst = this.analysts.find((analyst) => {
						return analyst.uid === shiftsChange.newAnalystId
					})
					const oldShift = this.shifts.find((shift) => {
						return shift.id === parseInt(shiftsChange.oldShiftsId)
					})
					oldShift.oldAnalystId = oldShift.userId
					oldShift.analystId = shiftsChange.newAnalystId
					oldShift.saveChanges = false

					await axios.put(generateUrl(`/apps/shifts/shifts/${oldShift.id}`), oldShift)
					const newShift = this.shifts.find((shift) => {
						return shift.id === parseInt(shiftsChange.newShiftsId)
					})
					if (newShift) {
						newShift.oldAnalystId = newShift.userId
						newShift.analystId = shiftsChange.oldAnalystId
						newShift.saveChanges = false

						await axios.put(generateUrl(`/apps/shifts/shifts/${newShift.id}`), newShift)
					}
					// fetches and updates shifts
					await updateExistingCalendarObjectFromShiftsChange(oldShift, newShift, oldAnalyst, newAnalyst)
					await this.$store.dispatch('updateShifts')
				}
				// updates shiftsChange
				await axios.put(generateUrl(`/apps/shifts/shiftsChange/${shiftsChange.id}`), shiftsChange)
				await this.$store.dispatch('updateShiftsChanges')
			} catch (e) {
				if (e.message.includes('Could not find corresponding Event')) {
					console.warn(e)
					showWarning(t('shifts'), 'Couldn\'t find corrensponding Calender-Entry')
				} else {
					console.error(e)
					showError(t('shifts', 'Could not save shifts Changes'))
				}
			}
		},
		// returns string detailing the Shift by given Shiftid
		getShiftsDetailsByShiftsIdAsString(shiftId) {
			const shift = this.shifts.find((shift) => {
				return shift.id === parseInt(shiftId)
			})
			if (shift) {
				const date = new Date(shift.date)
				const options = {
					month: 'short',
					year: 'numeric',
					weekday: 'long',
					day: 'numeric',
				}
				const dateString = date.toLocaleDateString('de-DE', options)

				return shift.shiftsType.name + ' ' + dateString
			} else {
				return ''
			}
		},
		// returns string of predetermined format with given date
		getDateString(date) {
			const dateObj = new Date(date)
			const options = {
				month: 'short',
				year: 'numeric',
				weekday: 'long',
				day: 'numeric',
			}
			const dateString = dateObj.toLocaleDateString('de-DE', options)
			const timeString = dateObj.toLocaleTimeString('de-DE')

			return dateString + ' ' + timeString
		},
		deleteRequest(request) {
			this.$store.dispatch('deleteRequest', request)
		},
	},
}
</script>
