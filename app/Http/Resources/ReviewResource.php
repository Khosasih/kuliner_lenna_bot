<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'author_name' => $this->authorName,
            'rating' => $this->rating,
            'text' => $this->text,
            'relative_time' => $this->relativeTime,
        ];
    }
}
