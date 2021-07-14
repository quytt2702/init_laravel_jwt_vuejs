<?php

namespace App\Repositories\Contracts;

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
     * Delete multiple records
     *
     * @param array  $value
     * @param int    $sizeBatch
     * @param string $column
     */
    public function batchDelete(array $value, int $sizeBatch = 500, string $column = 'id');

    /**
     * Update multiple records
     *
     * @param array  $ids
     * @param array  $dataUpdate
     * @param int    $chunkSize
     * @param string $column
     */
    public function batchUpdate(array $ids, array $dataUpdate, int $chunkSize = 500, string $column = '_id');

    /**
     * Insert multiple records
     *
     * @param array $data
     * @param int   $sizeBatch
     */
    public function batchInsert(array $data, int $sizeBatch = 500);

    /**
     * Find by id and for lock update
     *
     * @param mixed $id
     * @param bool  $strict
     *
     * @return Model|null|mixed
     */
    public function findByIdAndLock($id, bool $strict = true);

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
     * @return Model|null|mixed
     */
    public function firstOrFail($columns = ['*']);
}
