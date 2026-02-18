<?php

namespace App\Observers;

use App\Models\Filing;
use App\Services\AiProcessor;
use App\Jobs\EnrichFilingWithFinancials;

class FilingObserver
{

    public function __construct(protected AiProcessor $processor){}

    /**
     * Handle the Filing "created" event.
     */
    public function created(Filing $filing): void
    {
      \Log::info("Observer triggered for: " .$filing->title);
      $this->processor->analyze($filing);

      EnrichFilingWithFinancials::dispatch($filing);
    }

    /**
     * Handle the Filing "updated" event.
     */
    public function updated(Filing $filing): void
    {
        //
    }

    /**
     * Handle the Filing "deleted" event.
     */
    public function deleted(Filing $filing): void
    {
        //
    }

    /**
     * Handle the Filing "restored" event.
     */
    public function restored(Filing $filing): void
    {
        //
    }

    /**
     * Handle the Filing "force deleted" event.
     */
    public function forceDeleted(Filing $filing): void
    {
        //
    }
}
