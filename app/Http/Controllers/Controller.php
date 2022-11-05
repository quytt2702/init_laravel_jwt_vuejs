<?php

namespace App\Http\Controllers;

use App\Traits\HasApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, HasApiResponse;

    /**
     * Add the pagination custom to meta
     *
     * @param LengthAwarePaginator|mixed $pagination Pagination
     *
     * @return void
     */
    protected function addPagination($pagination)
    {
        if ($pagination instanceof LengthAwarePaginator) {
            $total = $pagination->total();
            $page  = $pagination->currentPage();
            $limit = $pagination->perPage();

            self::addMetaResponse('pagination', [
                'current' => $page,
                'next'    => ($page * $limit) < $total ? $page + 1 : null,
                'prev'    => $page > 1 ? $page - 1 : null,
                'last'    => $pagination->lastPage(),
                'first'   => 1,
                'limit'   => $pagination->perPage(),
                'total'   => $total,
            ]);
        }
    }
}
