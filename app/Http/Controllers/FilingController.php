<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Filing;

class FilingController extends Controller
{
  public function index(): Response{
		return Inertia::render('Dashboard', [
			// Get ONLY news with a summary
			// Sort by newest first
			// Take top 50
			'filings' => Filing::whereNotNull('summary')
				->latest()
				->take(50)
				->get(),

			'stats' => [
				'positive' => Filing::where('sentiment', 'Positive')->count(),
				'negative' => Filing::where('sentiment', 'Negative')->count(),
				'neutral' => Filing::where('sentiment', 'Neutral')->count(),
				]
		]);
	}
}
