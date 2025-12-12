<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasCache
{
    /**
     * Cache duration in seconds
     */
    protected $cacheDuration = 3600; // 1 hour

    /**
     * Get cache key for model
     */
    protected function getCacheKey(string $suffix = ''): string
    {
        $modelName = class_basename($this);
        $modelId = $this->id ?? 'all';
        
        return Str::slug("{$modelName}_{$modelId}_{$suffix}");
    }

    /**
     * Get cache key for queries
     */
    protected function getQueryCacheKey(array $filters = []): string
    {
        $modelName = class_basename($this);
        $filtersHash = md5(serialize($filters));
        
        return "query_{$modelName}_{$filtersHash}";
    }

    /**
     * Clear all cache related to this model
     */
    protected function clearModelCache(): void
    {
        $modelName = class_basename($this);
        $prefix = Str::slug($modelName);
        
        Cache::tags([$prefix])->flush();
    }

    /**
     * Get cached data or store if not exists
     */
    protected function remember(string $key, callable $callback, int $duration = null)
    {
        $duration = $duration ?? $this->cacheDuration;
        
        return Cache::remember($key, $duration, $callback);
    }

    /**
     * Get cached data with tags or store if not exists
     */
    protected function rememberWithTags(array $tags, string $key, callable $callback, int $duration = null)
    {
        $duration = $duration ?? $this->cacheDuration;
        
        return Cache::tags($tags)->remember($key, $duration, $callback);
    }
}