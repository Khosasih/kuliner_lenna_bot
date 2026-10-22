<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'rating' => $this->rating,
            'user_ratings_total' => $this->userRatingsTotal,
            'price_level' => $this->priceLevel,
            'phone_number' => $this->phoneNumber,
            'website' => $this->website,
            'cuisines' => $this->cuisines,
        ];
    }
}
