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
  ];
}
