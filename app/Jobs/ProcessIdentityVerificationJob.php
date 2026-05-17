<?php

namespace App\Jobs;

use App\Models\IdentityVerification;
use App\Services\IdentityVerificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIdentityVerificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public function __construct(private readonly int $verificationId)
    {
    }

    public function handle(IdentityVerificationService $verificationService): void
    {
        $verification = IdentityVerification::find($this->verificationId);

        if (!$verification) {
            return;
        }

        $verificationService->process($verification);
    }
}
