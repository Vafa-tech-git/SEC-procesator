<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Filing;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Search for companies based on ticker or name for autocomplete.
     */
    public function search(Request $request)
    {
        $q = $request->get('q');
        
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // Optimized query with specific columns
        $companies = Filing::whereNotNull('ticker')
            ->where(function($query) use ($q) {
                $query->where('ticker', 'LIKE', "%{$q}%")
                      ->orWhere('title', 'LIKE', "%{$q}%");
            })
            ->select('ticker', 'title')
            ->orderBy('filed_at', 'desc')
            ->get()
            ->unique('ticker')
            ->take(8);

        return CompanyResource::collection($companies);
    }
}
