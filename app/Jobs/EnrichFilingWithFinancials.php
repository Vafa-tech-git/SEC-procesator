<?php

namespace App\Jobs;

use App\Models\Filing;
use App\Services\FinancialDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnrichFilingWithFinancials implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Filing $filing){}

    /**
     * Execute the job.
     */
    public function handle(FinancialDataService $service): void
    {
        $ticker = $service->findTicker($this->filing->title);

        if (!$ticker) return;

        $data = $service->getComprehensiveData($ticker);

        if (empty($data['metrics'])) return;

        $this->filing->update(array_merge([
            'ticker' => $ticker,
            'financial_history' => $data['history'],
            'estimated_eps' => $data['estimated_eps'],
        ], $data['metrics']));
    }
}
