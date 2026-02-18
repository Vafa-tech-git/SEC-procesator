<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Scheb\YahooFinanceApi\ApiClientFactory;

class FinancialDataService
{
    private string $finnhubKey;
    private string $baseUrl = 'https://finnhub.io/api/v1/';

    public function __construct()
    {
        $this->finnhubKey = config('services.finnhub.key');
    }

    /**
     * Master method: Find Ticker symbol using Finnhub -> Yahoo -> AI Fallback.
     * Respects Industry Standards for resilience and caching.
     */
    public function findTicker(string $companyName): ?string
    {
        return Cache::remember("ticker_search_" . md5($companyName), now()->addDays(30), function () use ($companyName) {
            // 1. Try Finnhub
            $ticker = $this->getTickerFromFinnhub($companyName);
            if ($ticker) return $ticker;

            // 2. Try Yahoo Fallback
            $ticker = $this->getTickerFromYahoo($companyName);
            if ($ticker) return $ticker;

            // 3. Try AI if everything fails
            return $this->getTickerFromAI($companyName);
        });
    }

    /**
     * Get detailed metrics and historical data.
     * Uses Service Layer Pattern and Caching for performance.
     */
    public function getComprehensiveData(string $ticker): array
    {
        return Cache::remember("comprehensive_metrics_{$ticker}", now()->addHours(12), function () use ($ticker) {
            $data = $this->getFinnhubData($ticker);

            // Yahoo fallback (basic metrics only)
            if (empty($data['metrics'])) {
                $data = $this->getYahooFallbackData($ticker);
            }

            return $data;
        });
    }

    private function getTickerFromFinnhub(string $name): ?string
    {
        try {
            $response = Http::get("{$this->baseUrl}search", [
                'q' => $name,
                'token' => $this->finnhubKey
            ]);

            if ($response->successful() && !empty($response->json()['result'])) {
                return $response->json()['result'][0]['symbol'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning("Finnhub ticker search failed: " . $e->getMessage());
        }

        return null;
    }

    private function getTickerFromYahoo(string $name): ?string
    {
        try {
            $cleanName = preg_replace('/\s*\(.*?\)|Inc\.?|Corp\.?|Ltd\.?|LLC/i', '', $name);
            $client = ApiClientFactory::createApiClient();
            $results = $client->search(trim($cleanName));

            if (!empty($results)) {
                return $results[0]->getSymbol();
            }
        } catch (\Exception $e) {
            Log::error("Yahoo search failed: " . $e->getMessage());
        }

        return null;
    }

    public function getTickerFromAI(string $name): ?string
    {
        $prompt = "What is the stock ticker symbol for '{$name}'. Return ONLY the ticker (e.g. AAPL) or NULL if unknown. DO NOT write sentences.";
        
        return Cache::remember("ai_ticker_" . md5($name), now()->addDays(30), function () use ($name, $prompt) {
            $ticker = null;
            try {
                $response = Http::timeout(30)->post(config('services.ollama.local_url'), [
                    'model' => config('services.ollama.model'),
                    'prompt' => $prompt,
                    'stream' => false
                ]);

                if ($response->successful()) {
                    $ticker = $response->json()['response'] ?? null;
                }
            } catch (\Exception $e) {
                try {
                    $response = Http::withToken(config('services.ollama.key'))
                        ->post(config('services.ollama.cloud_url'), [
                            'model' => config('services.ollama.model'),
                            'messages' => [['role' => 'user', 'content' => $prompt]],
                            'stream' => false,
                        ]);

                    if ($response->successful()) {
                        $ticker = $response->json()['message']['content'] ?? null;
                    }
                } catch (\Exception $cloudError) {
                    Log::error("AI ticker lookup failed: " . $cloudError->getMessage());
                }
            }

            if ($ticker) {
                $ticker = strtoupper(trim(str_replace(['.', ' '], '', $ticker)));
                if (preg_match('/^[A-Z]{1,5}$/', $ticker)) {
                    return $ticker;
                }
            }

            return null;
        });
    }

    private function getFinnhubData(string $ticker): array
    {
        try {
            // 1. Basic Metrics
            $metricsResp = Http::get("{$this->baseUrl}stock/metric", [
                'symbol' => $ticker,
                'metric' => 'all',
                'token' => $this->finnhubKey
            ]);

            // 2. Earnings Calendar (1 year interval: 6 months back, 6 months forward)
            $from = now()->subMonths(6)->format('Y-m-d');
            $to = now()->addMonths(6)->format('Y-m-d');

            $earningsResp = Http::get("{$this->baseUrl}calendar/earnings", [
                'symbol' => $ticker,
                'from' => $from,
                'to' => $to,
                'token' => $this->finnhubKey
            ]);

            $metrics = $metricsResp->json()['metric'] ?? [];
            $earnings = $earningsResp->json()['earningsCalendar'] ?? [];

            // Sort by date descending
            usort($earnings, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));

            // Find the most recent or upcoming estimate
            $nextEarnings = collect($earnings)
                ->filter(fn($e) => empty($e['epsActual']) && ($e['date'] ?? '') >= now()->format('Y-m-d'))
                ->last();

            return [
                'metrics' => [
                    'pe_ratio' => $metrics['peTTM'] ?? null,
                    'ps_ratio' => $metrics['psTTM'] ?? null,
                    'profit_margin' => $metrics['netProfitMarginTTM'] ?? null,
                    'roe' => $metrics['roeTTM'] ?? null,
                    'debt_to_equity' => $metrics['totalDebt/totalEquityQuarterly'] ?? null,
                    'current_ratio' => $metrics['currentRatioQuarterly'] ?? null,
                    'dividend_yield' => $metrics['currentDividendYieldTTM'] ?? null,
                    'reported_eps' => $metrics['epsTTM'] ?? null,
                ],
                'history' => $earnings,
                'estimated_eps' => $nextEarnings['epsEstimate'] ?? ($earnings[0]['epsEstimate'] ?? null),
            ];
        } catch (\Exception $e) {
            Log::error("Finnhub data fetch failed: " . $e->getMessage());
            return ['metrics' => [], 'history' => [], 'estimated_eps' => null];
        }
    }

    private function getYahooFallbackData(string $ticker): array
    {
        try {
            $client = ApiClientFactory::createApiClient();
            $quote = $client->getQuote($ticker);

            if ($quote) {
                return [
                    'metrics' => [
                        'pe_ratio' => $quote->getTrailingPE(),
                        'reported_eps' => $quote->getEpsTrailingTwelveMonths(),
                        'dividend_yield' => $quote->getDividendYield(),
                    ],
                    'history' => [],
                    'estimated_eps' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::error("Yahoo fallback failed: " . $e->getMessage());
        }
        return ['metrics' => [], 'history' => [], 'estimated_eps' => null];
    }
}
