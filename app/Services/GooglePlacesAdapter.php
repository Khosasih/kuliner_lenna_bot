<?php

namespace App\Services;

use App\Contracts\RestaurantProviderInterface;
use App\DTOs\Restaurant\MenuItemDto;
use App\DTOs\Restaurant\RestaurantDto;
use App\DTOs\Restaurant\ReviewDto;
use App\DTOs\Restaurant\SearchRestaurantCriteriaDto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesAdapter implements RestaurantProviderInterface
{
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('restaurants.providers.google.api_key', '');
        $this->timeout = (int) config('restaurants.providers.google.timeout', 10);
    }

    public function providerName(): string
    {
        return 'google_places';
    }

    public function search(SearchRestaurantCriteriaDto $criteria): array
    {
        if (empty($this->apiKey)) {
            return $this->getMockFallbackRestaurants($criteria->query ?? 'Kuliner');
        }

        try {
            if ($criteria->latitude && $criteria->longitude) {
                $response = Http::timeout($this->timeout)->get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
                    'location' => "{$criteria->latitude},{$criteria->longitude}",
                    'radius' => $criteria->radius,
                    'type' => 'restaurant',
                    'key' => $this->apiKey,
                ]);
            } else {
                $response = Http::timeout($this->timeout)->get('https://maps.googleapis.com/maps/api/place/textsearch/json', [
                    'query' => ($criteria->query ?: 'restoran') . ' food restaurant',
                    'key' => $this->apiKey,
                ]);
            }

            $results = $response->json('results') ?? [];
            $mapped = [];

            foreach (array_slice($results, 0, $criteria->limit) as $item) {
                $mapped[] = new RestaurantDto(
                    id: (string) ($item['place_id'] ?? ''),
                    name: (string) ($item['name'] ?? 'Restoran'),
                    address: $item['formatted_address'] ?? $item['vicinity'] ?? null,
                    latitude: $item['geometry']['location']['lat'] ?? null,
                    longitude: $item['geometry']['location']['lng'] ?? null,
                    rating: isset($item['rating']) ? (float) $item['rating'] : null,
                    userRatingsTotal: $item['user_ratings_total'] ?? null,
                    priceLevel: isset($item['price_level']) ? str_repeat('💰', (int) $item['price_level']) : null,
                    cuisines: $item['types'] ?? [],
                    raw: $item
                );
            }

            return $mapped;
        } catch (\Throwable $e) {
            Log::error('Google Places Search failed: ' . $e->getMessage());
            return $this->getMockFallbackRestaurants($criteria->query ?? 'Kuliner');
        }
    }

    public function findById(string $id): ?RestaurantDto
    {
        if (empty($this->apiKey)) {
            $mock = $this->getMockFallbackRestaurants('Detail');
            return $mock[0] ?? null;
        }

        $res = Http::timeout($this->timeout)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $id,
            'fields' => 'place_id,name,formatted_address,geometry,rating,user_ratings_total,formatted_phone_number,website',
            'key' => $this->apiKey,
        ]);

        $item = $res->json('result');
        if (!$item)
            return null;

        return new RestaurantDto(
            id: $item['place_id'],
            name: $item['name'],
            address: $item['formatted_address'] ?? null,
            latitude: $item['geometry']['location']['lat'] ?? null,
            longitude: $item['geometry']['location']['lng'] ?? null,
            rating: isset($item['rating']) ? (float) $item['rating'] : null,
            phoneNumber: $item['formatted_phone_number'] ?? null,
            website: $item['website'] ?? null,
            raw: $item
        );
    }

    public function getMenu(string $restaurantId): array
    {
        return [
            new MenuItemDto(id: 'm1', name: 'Nasi Goreng Spesial', description: 'Nasi goreng bumbu racik telur & ayam suwir', price: 28000, category: 'Makanan Utama'),
            new MenuItemDto(id: 'm2', name: 'Ayam Bakar Madu', description: 'Ayam bakar rempah lumur madu legit', price: 35000, category: 'Makanan Utama'),
            new MenuItemDto(id: 'm3', name: 'Es Teh Manis Jumbo', description: 'Teh melati segar seduh dingin', price: 8000, category: 'Minuman'),
            new MenuItemDto(id: 'm4', name: 'Kopi Susu Gula Aren', description: 'Espresso robusta & gula aren murni', price: 18000, category: 'Minuman'),
        ];
    }

    public function getReviews(string $restaurantId): array
    {
        if (empty($this->apiKey)) {
            return [
                new ReviewDto(authorName: 'Budi Santoso', rating: 5.0, text: 'Rasa makanannya sangat otentik dan pelayanannya cepat!', relativeTime: '2 hari yang lalu'),
                new ReviewDto(authorName: 'Siti Rahma', rating: 4.5, text: 'Tempatnya nyaman untuk makan bersama keluarga.', relativeTime: '1 minggu yang lalu'),
            ];
        }

        $res = Http::timeout($this->timeout)->get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $restaurantId,
            'fields' => 'reviews',
            'key' => $this->apiKey,
        ]);

        $reviews = $res->json('result.reviews') ?? [];
        $out = [];
        foreach ($reviews as $rev) {
            $out[] = new ReviewDto(
                authorName: $rev['author_name'] ?? 'Anonim',
                rating: isset($rev['rating']) ? (float) $rev['rating'] : null,
                text: $rev['text'] ?? null,
                relativeTime: $rev['relative_time_description'] ?? null,
                authorPhotoUrl: $rev['profile_photo_url'] ?? null
            );
        }
        return $out;
    }

    private function getMockFallbackRestaurants(string $query): array
    {
        return [
            new RestaurantDto(id: 'resto-01', name: "Rumah Makan Padang {$query}", address: 'Jl. Merdeka No. 45, Jakarta Pusat', rating: 4.8, userRatingsTotal: 340, priceLevel: '💰💰'),
            new RestaurantDto(id: 'resto-02', name: "Kedai Kopi & Kuliner {$query}", address: 'Jl. Sudirman No. 12, Jakarta Selatan', rating: 4.6, userRatingsTotal: 180, priceLevel: '💰💰'),
            new RestaurantDto(id: 'resto-03', name: "Warung Spesial {$query} Tradisional", address: 'Jl. Pemuda No. 8, Jakarta Timur', rating: 4.5, userRatingsTotal: 95, priceLevel: '💰'),
        ];
    }
}
