<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
      return [
        'id' => $this->id,
        'title' => $this->title,
        'link' => $this->link,
        // Resume to brute category if normal fails
        'category' => $this->display_category ?? $this->category,
        'filed_at' => $this->filed_at,
        'summary' => $this->summary,
        'sentiment' => $this->sentiment,
        
        // Financial Metrics
        'ticker' => $this->ticker,
        'pe_ratio' => $this->pe_ratio,
        'ps_ratio' => $this->ps_ratio,
        'profit_margin' => $this->profit_margin,
        'roe' => $this->roe,
        'debt_to_equity' => $this->debt_to_equity,
        'current_ratio' => $this->current_ratio,
        'dividend_yield' => $this->dividend_yield,
        'reported_eps' => $this->reported_eps,
        'estimated_eps' => $this->estimated_eps,
        'financial_history' => $this->financial_history,
      ];
    }
}
