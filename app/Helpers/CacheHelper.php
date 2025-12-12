<?php

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Convert array to paginator
     */
    public static function arrayToPaginator(array $data, int $perPage, int $currentPage, int $total, array $options = []): LengthAwarePaginator
    {
        $items = Collection::make($data['data'] ?? $data);
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            $options
        );
    }

    /**
     * Generate cache key for paginated queries
     */
    public static function generatePaginatedCacheKey(string $model, array $filters, int $page = 1): string
    {
        $filtersJson = json_encode($filters);
        $filtersHash = md5($filtersJson);
        
        return "paginated_{$model}_{$filtersHash}_page_{$page}";
    }

    /**
     * Remember paginated query with cache
     */
    public static function rememberPaginated(
        string $cacheKey,
        callable $callback,
        int $duration = 1800,
        array $tags = []
    ) {
        if (!empty($tags)) {
            return Cache::tags($tags)->remember($cacheKey, $duration, $callback);
        }
        
        return Cache::remember($cacheKey, $duration, $callback);
    }
}