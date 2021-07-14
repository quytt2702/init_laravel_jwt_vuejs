<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityDeleting;

abstract class BaseRepositoryEloquent extends BaseRepository implements BaseRepositoryInterface
{
    /**
     * @param                $id
     * @param array|string[] $columns
     *
     * @return Model|null|mixed
     */
    public function findOrFail($id, $columns = ['*'])
    {
        $this->popCriteria(RequestCriteria::class);

        return parent::find($id, $columns);
    }

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        $this->popCriteria(RequestCriteria::class);
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * @param array|string[] $columns
     *
     * @return Model|null|mixed
     */
    public function first($columns = ['*'])
    {
        $this->popCriteria(RequestCriteria::class);

        return parent::first($columns);
    }

    /**
     * @param array|string[] $columns
     *
     * @return Model|null|mixed
     */
    public function firstOrFail($columns = ['*'])
    {
        $result = $this->first($columns);

        if (is_null($result)) {
            throw (new ModelNotFoundException())->setModel(get_class($this->model));
        }

        return $result;
    }

    /**
     * @param array $attributes
     *
     * @return Model|null
     */
    public function firstOrCreate(array $attributes = [])
    {
        $this->popCriteria(RequestCriteria::class);

        return parent::firstOrCreate($attributes);
    }

    /**
     * Delete multiple records
     *
     * @param array  $value
     * @param int    $sizeBatch
     * @param string $column
     */
    public function batchDelete(array $value, int $sizeBatch = 500, string $column = 'id')
    {
        $chunks = array_chunk($value, $sizeBatch);

        foreach ($chunks as $chunk) {
            $this->model->whereIn($column, $chunk)->delete();
        }
    }

    /**
     * Update multiple records
     *
     * @param array  $ids
     * @param array  $dataUpdate
     * @param int    $chunkSize
     * @param string $column
     */
    public function batchUpdate(array $ids, array $dataUpdate, int $chunkSize = 500, string $column = '_id')
    {
        $chunks = array_chunk($ids, $chunkSize);

        foreach ($chunks as $ids) {
            $this->model
                ->whereIn($column, $ids)
                ->update($dataUpdate);
        }
    }

    /**
     * Insert multiple records
     *
     * @param array $data
     * @param int   $sizeBatch
     */
    public function batchInsert(array $data, int $sizeBatch = 500)
    {
        $chunks = array_chunk($data, $sizeBatch);

        foreach ($chunks as $chunk) {
            $this->model->insert($chunk);
        }
    }

    /**
     * Find by id and for lock update
     *
     * @param mixed $id
     * @param bool  $strict
     *
     * @return Model|null|mixed
     */
    public function findByIdAndLock($id, bool $strict = true)
    {
        $this->model = $this->model
            ->lockForUpdate();

        if ($strict) {
            $result = $this->findOrFail($id);
        } else {
            $result = $this->find($id);
        }

        return $result;
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model         = $this->findOrFail($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        event(new RepositoryEntityDeleting($this, $model));

        $deleted = $model->delete();

        event(new RepositoryEntityDeleted($this, $originalModel));

        return $deleted;
    }

    /**
     * Sync relations
     *
     * @param      $id
     * @param      $relation
     * @param      $attributes
     * @param bool $detaching
     *
     * @return mixed
     */
    public function sync($id, $relation, $attributes, $detaching = true)
    {
        return $this->findOrFail($id)->{$relation}()->sync($attributes, $detaching);
    }
}
