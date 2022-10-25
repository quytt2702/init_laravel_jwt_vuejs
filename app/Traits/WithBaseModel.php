<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * Trait WithBaseModel
 *
 * @package App\Traits
 */
trait WithBaseModel
{
    use HasFactory;

    /**
     * Get table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        return with(new static())->getTable();
    }

    /**
     * Get column of table
     *
     * @return array
     */
    public static function getTableColumns()
    {
        /**
         * @var Model $model
         */
        $model = with(new static());

        return $model->getConnection()
            ->getSchemaBuilder()
            ->getColumnListing($model->getTable());
    }

    /**
     * Detect model is soft delete
     *
     * @return bool
     */
    public static function isTableSoftDelete()
    {
        return in_array(SoftDeletes::class, class_uses(self::class));
    }

    /**
     * @param $attribute
     *
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return Arr::has($this->attributes, $attribute);
    }

    /**
     * @param \DateTimeInterface $date
     *
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    /**
     * Update the creation and update timestamps.
     *
     * @return $this
     */
    public function updateTimestamps()
    {
        parent::updateTimestamps();

        return $this;
    }
}
