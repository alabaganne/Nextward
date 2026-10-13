<?php

namespace App\Jobs;

use App\Indexing\GoogleIndexing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendIndexingNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 55;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(GoogleIndexing $indexing): void
    {
        $indexing->send($this->notificationId);
    }
}
