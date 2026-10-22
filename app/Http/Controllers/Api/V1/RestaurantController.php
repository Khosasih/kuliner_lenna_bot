<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Restaurant\SearchRestaurantCriteriaDto;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRestaurantRequest;
use App\Http\Resources\MenuItemResource;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\ReviewResource;
use App\Services\RestaurantService;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestaurantController extends Controller
{
    public function __construct(
        protected RestaurantService $restaurantService
    ) {}

    public function index(SearchRestaurantRequest $request): JsonResponse
    {
        $criteria = SearchRestaurantCriteriaDto::fromArray($request->validated());
        $results = $this->restaurantService->search($criteria);
        return ApiResponse::success(
            RestaurantResource::collection($results),
            'Data restoran berhasil ditemukan.'
        );
    }

    public function show(string $id): JsonResponse
    {
        $restaurant = $this->restaurantService->findOrFail($id);
        return ApiResponse::success(
            new RestaurantResource($restaurant),
            'Detail restoran berhasil diambil.'
        );
    }

    public function menu(string $id): JsonResponse
    {
        $menuItems = $this->restaurantService->getMenu($id);
        return ApiResponse::success(
            MenuItemResource::collection($menuItems),
            'Daftar menu berhasil diambil.'
        );
    }

    public function reviews(string $id): JsonResponse
    {
        $reviews = $this->restaurantService->getReviews($id);
        return ApiResponse::success(
            ReviewResource::collection($reviews),
            'Daftar review berhasil diambil.'
        );
    }
}
