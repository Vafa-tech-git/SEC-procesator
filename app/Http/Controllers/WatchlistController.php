<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function toggle(Request $request){
        $request->validate([
            'ticker' => 'required|string|min:1|max:10',
        ]);

        $userId = Auth::id();
        $ticker = strtoupper($request->ticker);

        if (empty($ticker) || $ticker === 'NULL'){
            return back();
        }

        $exists = Watchlist::where('user_id', $userId)
                ->where('ticker', $ticker)
                ->first();

        if ($exists){
            $exists->delete();
            return back()->with('message', 'Removed from watchlist');
        }

        Watchlist::create([
            'user_id' => $userId,
            'ticker' => $ticker
        ]);

    return back()->with('message', 'Added to watchlist');
    }
}
