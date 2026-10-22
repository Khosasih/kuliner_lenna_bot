<?php

namespace App\Contracts;

use App\DTOs\Restaurant\MenuItemDto;
use App\DTOs\Restaurant\RestaurantDto;
use App\DTOs\Restaurant\ReviewDto;
use App\DTOs\Restaurant\SearchRestaurantCriteriaDto;

interface RestaurantProviderInterface
{
    /**
     * @return array<int, RestaurantDto>
     */
    public function search(SearchRestaurantCriteriaDto $criteria): array;

    public function findById(string $id): ?RestaurantDto;

    /**
     * @return array<int, MenuItemDto>
     */
    public function getMenu(string $restaurantId): array;

    /**
     * @return array<int, ReviewDto>
     */
    public function getReviews(string $restaurantId): array;

    public function providerName(): string;
}
