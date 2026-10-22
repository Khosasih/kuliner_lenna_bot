<?php

namespace App\DTOs\Restaurant;

readonly class MenuItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?float $price = null,
        public string $currency = 'IDR',
        public ?string $category = null,
        public ?string $photoUrl = null
    ) {}
}
