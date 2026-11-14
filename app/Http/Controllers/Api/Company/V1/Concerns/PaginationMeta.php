<?php

namespace App\Http\Controllers\Api\Company\V1\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait PaginationMeta
{
    protected function perPage(Request $request, int $default = 15): int
    {
        return min(50, max(1, (int) $request->integer('per_page', $default)));
    }

    /**
     * @return array<string, int>
     */
    protected function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
