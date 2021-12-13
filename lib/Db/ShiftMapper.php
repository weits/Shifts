<?php
/*
 * @copyright Copyright (c) 2021. Fabian Kirchesch <fabian.kirchesch@csoc.de>
 *
 * @author Fabian Kirchesch <fabian.kirchesch@csoc.de>
 */

namespace OCA\Shifts\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class ShiftMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'shifts', Shift::class);
	}

	/**
	 * Finds Shift by Shiftid
	 * @param int $id
	 * @return Shift
	 */
	public function find(int $id): Shift {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntity($qb);
	}

	/**
	 * Finds all Shifts by given userId
	 * @param string $userId
	 * @return array
	 */
	public function findById(string $userId): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts')
			->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
		return $this->findEntities($qb);
	}

	/**
	 * Fetches all Shifts by ShiftsType
	 */
	public function findByShiftsTypeId(int $id): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts')
			->where($qb->expr()->eq('shift_type_id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		return $this->findEntities($qb);
	}

	/**
	 * Fetches all Shifts
	 * @return array
	 */
	public function findAll(): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts');
		return $this->findEntities($qb);
	}

	/**
	 * Fetches shifts by Datestring
	 * @param $currentDate
	 * @param $type
	 * @return array
	 */
	public function findByDateAndType($currentDate, $type): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts')
			->where($qb->expr()->eq('date', $qb->createNamedParameter($currentDate)))
			->andWhere($qb->expr()->eq('shift_type_id', $qb->createNamedParameter($type)));
		return $this->findEntities($qb);
	}

	/**
	 * Fetches shifts by date, type and assignmentstatus
	 *
	 */
	public function findByDateTypeandAssignment($currentDate, $type): array {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('shifts')
			->where($qb->expr()->eq('date', $qb->createNamedParameter($currentDate)))
			->andWhere($qb->expr()->eq('shift_type_id', $qb->createNamedParameter($type)))
			->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter('-1')));
		return $this->findEntities($qb);
	}

	/**
	 * Fetches all shifts in timerange for archival display
	 * @param string $start
	 * @param string $end
	 * @return array
	 */
	public function findByTimeRange(string $start, string $end) : array {
		$startDate = date('Y-m-d', strtotime($start));
		$endDate = date('Y-m-d', strtotime($end));
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();
		$qb->select('user_id', 'shift_type_id', $qb->func()->count('*','num_shifts'))
			->from('shifts')
			->where($qb->expr()->neq('user_id', $qb->createNamedParameter('-1')))
			->andWhere($qb->expr()->gte('date', $qb->createNamedParameter($startDate)))
			->andWhere($qb->expr()->lt('date', $qb->createNamedParameter($endDate)))
			->groupBy('user_id', 'shift_type_id')
			->orderBy('user_id');
		$result = $qb->execute();
		return $result->fetchAll();
	}
}
