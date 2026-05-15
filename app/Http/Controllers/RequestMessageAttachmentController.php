<?php

namespace App\Http\Controllers;

use App\Models\RequestMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RequestMessageAttachmentController extends Controller
{
    public function download(RequestMessage $requestMessage)
    {
        $requestMessage->loadMissing('serviceRequest.service');
        $user = Auth::user();
        $serviceRequest = $requestMessage->serviceRequest;

        abort_if(!$serviceRequest || !$user, 403);

        $canDownload = false;

        if ($user->hasRole('citizen')) {
            $canDownload = $serviceRequest->user_id === $user->id;
        }

        if ($user->hasRole('municipality')) {
            $canDownload = $serviceRequest->service?->government_office_id === $user->government_office_id;
        }

        abort_if(!$canDownload, 404);

        if (!$requestMessage->attachment_path || !Storage::disk('public')->exists($requestMessage->attachment_path)) {
            return abort(404, 'The requested message attachment could not be found.');
        }

        return Storage::disk('public')->download(
            $requestMessage->attachment_path,
            basename($requestMessage->attachment_path)
        );
    }
}
