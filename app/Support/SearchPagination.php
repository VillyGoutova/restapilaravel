<?php

namespace App\Support;

use Illuminate\Pagination\Cursor;

final class SearchPagination
{
    public static function offsetFromCursor(?string $encodedCursor): int
    {
        if ($encodedCursor === null || $encodedCursor === '') {
            return 0;
        }

        $cursor = Cursor::fromEncoded($encodedCursor);

        if ($cursor === null) {
            return 0;
        }

        return max(0, (int) $cursor->parameter('offset', 0));
    }

    public static function maxTotalHits(): int
    {
        return max(1, (int) config('scout.meilisearch.max_total_hits', 10_000));
    }
}
