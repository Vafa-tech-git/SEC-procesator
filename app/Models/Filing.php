<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filing extends Model
{
  protected $fillable = [
    'title',
    'link',
    'category',
    'filed_at',
    'summary',
    'sentiment',
    'display_category',
    'cik',
    'ticker',
    'reported_eps',
    'estimated_eps',
    'profit_margin',
    'roe',
    'pe_ratio',
    'ps_ratio',
    'debt_to_equity',
    'current_ratio',
    'capex',
    'dividend_yield',
    'financial_history'
  ];

  protected $casts = [
    'financial_history' => 'array',
    'filed_at' => 'datetime',
  ];

}
