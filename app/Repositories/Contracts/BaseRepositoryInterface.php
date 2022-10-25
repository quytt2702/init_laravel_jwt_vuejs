<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Interface BaseRepositoryInterface.
 *
 * @package namespace App\Repositories\Contracts;
 */
interface BaseRepositoryInterface extends RepositoryInterface
{
    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function forceDelete($id);

    /**
     * Delete multiple records
     *
     * @param array  $value
     * @param int    $sizeBatch
     * @param string $column
     *
     * @return int
     */
    public function batchDelete(array $value, int $sizeBatch = 500, string $column = 'id');

    /**
     * Update multiple records
     *
     * @param array  $ids
     * @param array  $dataUpdate
     * @param int    $chunkSize
     * @param string $column
     *
     * @return int
     */
    public function batchUpdate(array $ids, array $dataUpdate, int $chunkSize = 500, string $column = 'id');

    /**
     * Insert multiple records
     *
     * @param array $data
     * @param int   $sizeBatch
     */
    public function batchInsert(array $data, int $sizeBatch = 500);

    /**
     * @param                $id
     * @param array|string[] $columns
     *
     * @return Model|null|mixed
     */
    public function findOrFail($id, $columns = ['*']);

    /**
     * @param array|string[] $columns
     *
     * @return Model|null
     */
    public function firstOrFail($columns = ['*']);

    /**
     * Parse query builder from repository
     *
     * @return Builder|mixed
     */
    public function parseQueryBuilder();

    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     *
     * @return $this
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * @return $this
     */
    public function withTrashed();

    /**
     * @return $this
     */
    public function onlyTrashed();

    /**
     * Determine if any rows exist for the current query.
     *
     * @return mixed
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function exists();

    /**
     * Retrieve first data of repository
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*']);

    /**
     * @param array|string[] $columns
     *
     * @return $this
     */
    public function withLockForUpdate();

    /**
     * Add subselect queries to sum the relations.
     *
     * @param mixed $relations
     *
     * @return $this
     */
    public function withSum($relations, $column);

    /**
     * Add subselect queries to exists the relations.
     *
     * @param mixed $relations
     *
     * @return $this
     */
    public function withExists($relations);

    /**
     * Restore a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function restore($id);
}
