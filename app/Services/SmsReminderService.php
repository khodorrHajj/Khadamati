<?php

namespace App\Services;

use App\Models\Appointment;

class SmsReminderService
{
    public function sendAppointmentReminder(Appointment $appointment): bool
    {
        // Stub only. SMS delivery can be connected later through config/services.php.
        return false;
    }
}
