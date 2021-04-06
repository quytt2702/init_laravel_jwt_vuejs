<?php

namespace App\Repositories;

use App\Exceptions\CustomException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseRepository
 *
 * @package App\Repositories
 */
abstract class BaseRepository
{
    /**
     * @var Model|Builder
     */
    protected $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->makeModel();
        $this->boot();
    }

    /**
     * Perform any actions required when init repository.
     */
    protected function boot()
    {
        //
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * Make model from model class name
     *
     * @return $this
     *
     * @throws CustomException
     */
    protected function makeModel()
    {
        $model = app($this->model());

        if (!($model instanceof Model)) {
            throw_custom_exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Refresh model
     *
     * @return $this
     *
     * @throws CustomException
     */
    public function resetModel()
    {
        $this->makeModel();

        return $this;
    }

    /**
     * Get all data of repository
     *
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection|Model[]
     *
     * @throws CustomException
     */
    public function all($columns = ['*'])
    {
        $results = $this->model->all($columns);

        $this->resetModel();

        return $results;
    }

    /**
     * Paginate the given query of repository.
     *
     * @param  int|null  $limit
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws CustomException
     */
    public function paginate($limit = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $results = $this->model->paginate($limit, $columns, $pageName, $page);

        $this->resetModel();

        return $results;
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param $id
     * @param bool $strict
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed
     */
    public function find($id, $strict = false)
    {
        return $strict
            ? $this->model->findOrFail($id)
            : $this->model->find($id);
    }

    /**
     * First result of repository or create new result
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed
     *
     * @throws CustomException
     */
    public function firstOrCreate(array $attributes = [])
    {
        $result = $this->model->firstOrCreate($attributes);

        $this->resetModel();

        return $result;
    }

    /**
     * Create a new result in repository
     *
     * @param array $data
     *
     * @return Model|mixed
     *
     * @throws CustomException
     */
    public function create(array $data)
    {
        $result = $this->model->create($data);

        $this->resetModel();

        return $result;
    }

    /**
     * Update model in repository
     *
     * @param array $data
     * @param $instance
     *
     * @return Model|mixed
     *
     * @throws CustomException
     */
    public function update(array $data, $instance)
    {
        if (!($instance instanceof Model)) {
            $instance = $this->find($instance, true);
        }

        $instance->update($data);
        $this->resetModel();

        return $instance;
    }

    /**
     * Delete model in repository
     *
     * @param mixed $instance
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete($instance)
    {
        if (!($instance instanceof Model)) {
            $instance = $this->find($instance, true);
        }

        $deleted = $instance->delete();
        $this->resetModel();

        return $deleted;
    }

    /**
     * Insert Multiple data
     *
     * @param array $data
     *
     * @return bool
     */
    public function insert(array $data)
    {
        return $this->model->insert($data);
    }

    /**
     * Delete with condition in repository
     *
     * @param array $where
     *
     * @return mixed
     *
     * @throws CustomException
     */
    public function deleteWhere(array $where)
    {
        $deleted = $this->model->where($where)->delete();
        $this->resetModel();

        return $deleted;
    }

    /**
     * Get list data in repository
     *
     * @param array|string $columns Columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Exception
     */
    public function get($columns = ['*'])
    {
        $results = $this->model->get($columns);
        $this->resetModel();

        return $results;
    }
}
