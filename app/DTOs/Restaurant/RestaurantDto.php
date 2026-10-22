<?php

namespace App\DTOs\Restaurant;

readonly class RestaurantDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $address = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $rating = null,
        public ?int $userRatingsTotal = null,
        public ?string $priceLevel = null,
        public ?string $phoneNumber = null,
        public ?string $website = null,
        public ?string $photoUrl = null,
        public array $cuisines = [],
        public array $raw = []
    ) {}
}
