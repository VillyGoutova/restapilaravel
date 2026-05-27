<?php

namespace App\Pagination;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;

/**
 * Cursor paginator for Meilisearch offset/limit search (avoids deep page offsets).
 */
class SearchOffsetCursorPaginator extends CursorPaginator
{
    public function __construct(
        $items,
        int $perPage,
        ?Cursor $cursor = null,
        protected int $currentOffset = 0,
        array $options = [],
    ) {
        parent::__construct($items, $perPage, $cursor, $options);
    }

    public function nextCursor(): ?Cursor
    {
        if (! $this->hasMorePages()) {
            return null;
        }

        return new Cursor(
            ['offset' => $this->currentOffset + $this->perPage()],
            true,
        );
    }

    public function previousCursor(): ?Cursor
    {
        $previousOffset = $this->currentOffset - $this->perPage();

        if ($previousOffset < 0) {
            return null;
        }

        return new Cursor(
            ['offset' => $previousOffset],
            false,
        );
    }
}
