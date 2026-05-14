<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppointmentEmailHook
{
    public function send(Appointment $appointment, string $eventLabel): void
    {
        $recipient = $appointment->user?->email;

        if (!$recipient) {
            return;
        }

        $serviceName = $appointment->serviceRequest?->service?->name ?? 'service';
        $officeName = $appointment->governmentOffice?->name ?? 'government office';
        $slotText = $appointment->timeSlot && $appointment->timeSlot->starts_at
            ? $appointment->timeSlot->starts_at->format('Y-m-d H:i')
            : 'to be confirmed';

        try {
            Mail::raw(
                "Your appointment for {$serviceName} at {$officeName} was {$eventLabel}. Scheduled time: {$slotText}.",
                function ($message) use ($recipient, $eventLabel) {
                    $message->to($recipient)->subject("Appointment {$eventLabel}");
                }
            );

            $appointment->forceFill([
                'email_notified_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('Appointment email hook failed.', [
                'appointment_id' => $appointment->id,
                'event' => $eventLabel,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
