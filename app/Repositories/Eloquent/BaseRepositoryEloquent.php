<?php

namespace App\Repositories\Eloquent;

use App\Helpers\CacheHelper;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Repositories\Criteria\OptimizeRequestCriteria;
use Illuminate\Database\Eloquent\Builder;
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
        $this->popCriteria(OptimizeRequestCriteria::class);
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
        $this->popCriteria(OptimizeRequestCriteria::class);
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
        $this->popCriteria(OptimizeRequestCriteria::class);
        $this->popCriteria(RequestCriteria::class);

        return parent::first($columns);
    }

    /**
     * Retrieve data array for populate field select
     * Compatible with Laravel 5.3
     *
     * @param string      $column
     * @param string|null $key
     *
     * @return \Illuminate\Support\Collection|array
     */
    public function pluck($column, $key = null)
    {
        $this->applyScope();

        $results = parent::pluck($column, $key);

        $this->resetModel();
        $this->resetScope();

        return $results;
    }

    /**
     * @param array|string[] $columns
     *
     * @return Model|null
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
     * Determine if any rows exist for the current query.
     *
     * @return mixed
     * @throws \Prettus\Repository\Exceptions\RepositoryException
     */
    public function exists()
    {
        $this->applyCriteria();
        $this->applyScope();

        $exist = $this->model->exists();

        $this->resetModel();
        $this->resetScope();

        return $exist;
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

    /**
     * Parse query builder from repository
     *
     * @return Builder|mixed
     */
    public function parseQueryBuilder()
    {
        $this->popCriteria(OptimizeRequestCriteria::class);
        $this->popCriteria(RequestCriteria::class);
        $this->applyCriteria();
        $this->applyScope();
        $query = $this->model;

        $this->resetModel();

        return $query;
    }

    /**
     * @return $this
     */
    public function withTrashed()
    {
        $this->model = $this->model->withTrashed();

        return $this;
    }

    /**
     * @return $this
     */
    public function onlyTrashed()
    {
        $this->model = $this->model->onlyTrashed();

        return $this;
    }

    /**
     * @param array|string[] $columns
     *
     * @return $this
     */
    public function withLockForUpdate()
    {
        $this->model = $this->model->lockForUpdate();

        return $this;
    }

    /**
     * Add subselect queries to sum the relations.
     *
     * @param mixed $relations
     *
     * @return $this
     */
    public function withSum($relations, $column)
    {
        $this->model = $this->model->withSum($relations, $column);

        return $this;
    }

    /**
     * Add subselect queries to exists the relations.
     *
     * @param mixed $relations
     *
     * @return $this
     */
    public function withExists($relations)
    {
        $this->model = $this->model->withExists($relations);

        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return Model|null
     */
    public function firstOrCreate(array $attributes = [])
    {
        $this->popCriteria(OptimizeRequestCriteria::class);
        $this->popCriteria(RequestCriteria::class);

        $result = parent::firstOrCreate($attributes);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $result;
    }

    /**
     * Delete multiple records
     *
     * @param array  $value
     * @param int    $sizeBatch
     * @param string $column
     *
     * @return int
     */
    public function batchDelete(array $value, int $sizeBatch = 500, string $column = 'id')
    {
        $this->applyScope();
        $deleted = 0;
        $chunks  = array_chunk($value, $sizeBatch);

        event(new RepositoryEntityDeleting($this, $this->model->getModel()));

        foreach ($chunks as $chunk) {
            $deleted += $this->model->whereIn($column, $chunk)->delete();
        }

        event(new RepositoryEntityDeleted($this, $this->model->getModel()));

        $this->resetModel();
        $this->resetScope();

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $deleted;
    }

    /**
     * @param array $where
     *
     * @return int
     * @throws \Exception
     */
    public function deleteWhere(array $where)
    {
        $deleted = parent::deleteWhere($where);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $deleted;
    }

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
    public function batchUpdate(array $ids, array $dataUpdate, int $chunkSize = 500, string $column = 'id')
    {
        $this->applyScope();
        $updated = 0;
        $chunks  = array_chunk($ids, $chunkSize);

        event(new RepositoryEntityUpdating($this, $this->model->getModel()));

        foreach ($chunks as $ids) {
            $updated += $this->model
                ->whereIn($column, $ids)
                ->update($dataUpdate);
        }

        event(new RepositoryEntityUpdated($this, $this->model->getModel()));

        $this->resetModel();
        $this->resetScope();

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $updated;
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

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }
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

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $deleted;
    }

    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function forceDelete($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model         = $this->withTrashed()->findOrFail($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        event(new RepositoryEntityDeleting($this, $model));

        $deleted = $model->forceDelete();

        event(new RepositoryEntityDeleted($this, $originalModel));

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $deleted;
    }

    /**
     * @param array $attributes
     *
     * @return Model
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function create(array $attributes)
    {
        $result = parent::create($attributes);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $result;
    }

    /**
     * @param array $attributes
     * @param       $id
     *
     * @return Model
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function update(array $attributes, $id)
    {
        $result = parent::update($attributes, $id);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $result;
    }

    /**
     * @param array $attributes
     * @param array $values
     *
     * @return Model
     * @throws \Prettus\Validator\Exceptions\ValidatorException
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        $result = parent::updateOrCreate($attributes, $values);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $result;
    }

    /**
     * @param array $attributes
     *
     * @return Model
     * @throws \Exception
     */
    public function firstOrNew(array $attributes = [])
    {
        $result = parent::firstOrNew($attributes);

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $result;
    }

    /**
     * Restore a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function restore($id)
    {
        $this->applyScope();

        $temporarySkipPresenter = $this->skipPresenter;
        $this->skipPresenter(true);

        $model         = $this->onlyTrashed()->findOrFail($id);
        $originalModel = clone $model;

        $this->skipPresenter($temporarySkipPresenter);
        $this->resetModel();

        event(new RepositoryEntityDeleting($this, $model));

        $deleted = $model->restore();

        event(new RepositoryEntityDeleted($this, $originalModel));

        // Remove cache tag
        if (!empty($this->cacheTag())) {
            $this->cacheHelper()->clearWithTags($this->cacheTag());
        }

        return $deleted;
    }

    /**
     * @return CacheHelper
     */
    protected function cacheHelper()
    {
        return app(CacheHelper::class);
    }

    /**
     * @return null|string
     */
    protected function cacheTag()
    {
        return null;
    }
}
