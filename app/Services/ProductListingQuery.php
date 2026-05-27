<?php

namespace App\Services;

use App\Models\Product;
use App\Pagination\SearchOffsetCursorPaginator;
use App\Support\SearchPagination;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\Paginator;
use Laravel\Scout\Builder as ScoutBuilder;

class ProductListingQuery
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly array $filters,
        private readonly int $perPage,
        private readonly ?int $priceMin,
        private readonly ?int $priceMax,
        private readonly bool $includeCategories,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function make(
        array $filters,
        int $perPage,
        ?int $priceMin,
        ?int $priceMax,
        bool $includeCategories,
    ): self {
        return new self($filters, $perPage, $priceMin, $priceMax, $includeCategories);
    }

    public function paginate(): CursorPaginator
    {
        if (! empty($this->filters['q'])) {
            return $this->searchCursorPaginate();
        }

        return $this->databasePaginate();
    }

    private function searchCursorPaginate(): CursorPaginator
    {
        $offset = SearchPagination::offsetFromCursor($this->filters['cursor'] ?? null);
        $maxTotalHits = SearchPagination::maxTotalHits();

        if ($offset >= $maxTotalHits) {
            return $this->emptySearchCursorPaginator($offset);
        }

        $limit = min($this->perPage + 1, $maxTotalHits - $offset);

        $models = $this->applyScoutFilters(Product::search($this->filters['q']))
            ->options([
                'offset' => $offset,
                'limit' => $limit,
            ])
            ->query(fn (Builder $query) => $this->applySelectAndEagerLoads($query))
            ->get();

        $cursor = isset($this->filters['cursor'])
            ? Cursor::fromEncoded($this->filters['cursor'])
            : null;

        return (new SearchOffsetCursorPaginator(
            $models,
            $this->perPage,
            $cursor,
            $offset,
            [
                'path' => Paginator::resolveCurrentPath(),
                'cursorName' => 'cursor',
                'parameters' => request()->except('cursor'),
            ],
        ))->withQueryString();
    }

    private function emptySearchCursorPaginator(int $offset): SearchOffsetCursorPaginator
    {
        $cursor = isset($this->filters['cursor'])
            ? Cursor::fromEncoded($this->filters['cursor'])
            : null;

        return (new SearchOffsetCursorPaginator(
            collect(),
            $this->perPage,
            $cursor,
            $offset,
            [
                'path' => Paginator::resolveCurrentPath(),
                'cursorName' => 'cursor',
                'parameters' => request()->except('cursor'),
            ],
        ))->withQueryString();
    }

    private function databasePaginate(): CursorPaginator
    {
        return $this->applyDatabaseFilters(Product::query())
            ->orderBy('id')
            ->cursorPaginate($this->perPage)
            ->withQueryString();
    }

    private function applyScoutFilters(ScoutBuilder $builder): ScoutBuilder
    {
        $builder->where('is_active', true);

        if ($this->priceMin !== null) {
            $builder->where('price', '>=', $this->priceMin);
        }

        if ($this->priceMax !== null) {
            $builder->where('price', '<=', $this->priceMax);
        }

        if (! empty($this->filters['category_ids'])) {
            $builder->whereIn('category_ids', $this->filters['category_ids']);
        }

        return $builder;
    }

    private function applyDatabaseFilters(Builder $query): Builder
    {
        return $query
            ->tap(fn (Builder $query) => $this->applySelectAndEagerLoads($query))
            ->where('is_active', true)
            ->when(
                $this->priceMin !== null,
                fn (Builder $query) => $query->where('price', '>=', $this->priceMin)
            )
            ->when(
                $this->priceMax !== null,
                fn (Builder $query) => $query->where('price', '<=', $this->priceMax)
            )
            ->when(
                ! empty($this->filters['category_ids']),
                fn (Builder $query) => $query->whereIn('id', function ($subquery) {
                    $subquery
                        ->select('product_id')
                        ->from('category_product')
                        ->whereIn('category_id', $this->filters['category_ids']);
                })
            );
    }

    private function applySelectAndEagerLoads(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'price',
                'title',
                'image',
                'is_active',
                'created_at',
            ])
            ->when(
                $this->includeCategories,
                fn (Builder $query) => $query->with('categories:id,title,products_count')
            );
    }
}
