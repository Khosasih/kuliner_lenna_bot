<?php

namespace App\DTOs\Restaurant;

readonly class ReviewDto
{
    public function __construct(
        public string $authorName,
        public ?float $rating = null,
        public ?string $text = null,
        public ?string $relativeTime = null,
        public ?string $authorPhotoUrl = null
    ) {}
}
