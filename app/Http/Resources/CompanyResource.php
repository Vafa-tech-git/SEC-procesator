<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ticker' => $this->ticker,
            'name' => $this->cleanName($this->title),
        ];
    }

    /**
     * Internal helper to clean company names from titles.
     */
    private function cleanName($title) {
        if (!$title) return '';

        $cleaned = explode(' - ', $title);
        $name = count($cleaned) > 1 ? $cleaned[1] : $title;
        
        // Remove CIK/parentheses
        $name = preg_replace('/\s*\(.*?\)/', '', $name);
        
        // Remove common legal suffixes
        $name = preg_replace('/\s*(Inc\.?|Corp\.?|Ltd\.?|LLC|PLC|LP|SA|N\.A\.?|Group)(\/.*?)?$/i', '', $name);
        
        // Final cleanup of trailing symbols
        return trim(preg_replace('/[\/\s,]+$/', '', $name));
    }
}
