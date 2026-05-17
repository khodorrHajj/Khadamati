<?php

namespace App\Jobs;

use App\Models\NationalId;
use App\Services\PendingNationalIdService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPendingNationalIdJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public function __construct(private readonly int $nationalIdId)
    {
    }

    public function handle(PendingNationalIdService $pendingNationalIdService): void
    {
        $nationalId = NationalId::find($this->nationalIdId);

        if (!$nationalId) {
            return;
        }

        $pendingNationalIdService->process($nationalId);
    }
}
