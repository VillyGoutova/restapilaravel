<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'price',
        'title',
        'content',
        'image',
        'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price / 100, 2, '.', '');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->timestamp,
            'category_ids' => $this->relationLoaded('categories')
                ? $this->categories->pluck('id')->all()
                : $this->categories()->pluck('categories.id')->all(),
        ];
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with('categories:id');
    }
}
