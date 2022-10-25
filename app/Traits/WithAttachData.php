<?php

namespace App\Traits;

trait WithAttachData
{
    /**
     * @var array
     */
    protected $with = [];

    /**
     * Get with attach data
     *
     * @return array
     */
    public function getAttachData()
    {
        return $this->with;
    }

    /**
     * Set with attach with
     *
     * @param array $with
     *
     * @return $this
     */
    public function with(array $with)
    {
        $this->with = $with;

        return $this;
    }
}
