<?php

namespace Tests\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait WithPaginator
{
    /**
     * Crée un paginateur avec les items fournis
     */
    protected function makePaginator(
        array $items = [],
        int $perPage = 15,
        int $currentPage = 1
    ): LengthAwarePaginator {
        return new LengthAwarePaginator(
            items: $items,
            total: count($items),
            perPage: $perPage,
            currentPage: $currentPage,
        );
    }

    /**
     * Crée un paginateur vide
     */
    protected function makeEmptyPaginator(): LengthAwarePaginator
    {
        return $this->makePaginator([]);
    }
}
