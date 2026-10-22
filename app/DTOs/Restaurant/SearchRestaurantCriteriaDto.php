<?php

namespace App\DTOs\Restaurant;

readonly class SearchRestaurantCriteriaDto
{
    public function __construct(
        public ?string $query = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public int $radius = 3000,
        public int $limit = 10
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? null,
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            radius: (int) ($data['radius'] ?? 3000),
            limit: (int) ($data['limit'] ?? 10)
        );
    }
}
