<?php

namespace App\Services;

use App\Contracts\RestaurantProviderInterface;
use App\DTOs\Restaurant\MenuItemDto;
use App\DTOs\Restaurant\RestaurantDto;
use App\DTOs\Restaurant\ReviewDto;
use App\DTOs\Restaurant\SearchRestaurantCriteriaDto;

class RestaurantService
{
    public function __construct(
        protected RestaurantProviderInterface $provider
    ) {}

    /**
     * @return array<int, RestaurantDto>
     */
    public function search(SearchRestaurantCriteriaDto $criteria): array
    {
        return $this->provider->search($criteria);
    }

    public function findOrFail(string $id): RestaurantDto
    {
        $restaurant = $this->provider->findById($id);
        if (!$restaurant) {
            abort(404, 'Data restoran tidak ditemukan.');
        }
        return $restaurant;
    }

    /**
     * @return array<int, MenuItemDto>
     */
    public function getMenu(string $restaurantId): array
    {
        return $this->provider->getMenu($restaurantId);
    }

    /**
     * @return array<int, ReviewDto>
     */
    public function getReviews(string $restaurantId): array
    {
        return $this->provider->getReviews($restaurantId);
    }
}
