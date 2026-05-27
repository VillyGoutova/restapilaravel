<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // Database stores cents, API returns normal decimal price.
            'price' => number_format($this->price / 100, 2, '.', ''),

            'title' => $this->title,
            'image' => $this->image,

            'categories' => CategoryResource::collection(
                $this->whenLoaded('categories')
            ),
        ];
    }
}