<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Filing;
use App\Http\Resources\FilingResource;

class FilingController extends Controller
{
    /**
     * Display the main dashboard with filings and sentiment stats.
     */
	public function index(): Response
    {
        $user = auth()->user();
        $watchlist = $user ? $user->watchlists->pluck('ticker')->toArray() : [];

		$filings = Filing::whereNotNull('summary')
			->latest()
			->take(50)
			->get();

        return Inertia::render('Dashboard', [
            'filings' => FilingResource::collection($filings),
            'stats' => [
                'positive' => Filing::where('sentiment', 'Positive')->count(),
                'negative' => Filing::where('sentiment', 'Negative')->count(),
                'neutral' => Filing::where('sentiment', 'Neutral')->count(),
            ],
            'watchlist' => $watchlist
        ]);
	}
}
