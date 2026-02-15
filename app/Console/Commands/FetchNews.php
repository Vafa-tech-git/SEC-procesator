<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest filings from SEC';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\SecFetcher $fetcher)
    {
        $this->info('Starting SEC scan...');
        $result = $fetcher->fetch();
        $this->info($result);
    }
}
