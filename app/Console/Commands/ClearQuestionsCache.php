<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearQuestionsCache extends Command
{
    protected $signature = 'cache:questions {--all : Clear all question related cache}';
    protected $description = 'Clear questions cache';

    public function handle()
    {
        $tags = ['questions', 'filters'];
        
        if ($this->option('all')) {
            Cache::tags($tags)->flush();
            $this->info('All questions cache cleared!');
        } else {
            // Limpa apenas cache antigo
            Cache::forget('questions_stats');
            Cache::forget('questions_count_total');
            Cache::forget('questions_count_active');
            Cache::forget('questions_count_inactive');
            Cache::forget('questions_by_difficulty');
            $this->info('Questions statistics cache cleared!');
        }
        
        return 0;
    }
}