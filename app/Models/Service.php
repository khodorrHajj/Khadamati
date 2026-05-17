<?php

namespace App\Models;

use App\Support\LebaneseCurrency;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'government_office_id',
        'service_category_id',
        'name',
        'description',
        'price',
        'duration_days',
        'duration_days_max',
        'required_documents',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function formattedPrice(): string
    {
        return LebaneseCurrency::format($this->price);
    }

    public function durationFromDays(): int
    {
        return max(1, (int) $this->duration_days);
    }

    public function durationToDays(): int
    {
        return max($this->durationFromDays(), (int) ($this->duration_days_max ?: $this->duration_days));
    }

    public function durationLabel(): string
    {
        $from = $this->durationFromDays();
        $to = $this->durationToDays();

        if ($from === $to) {
            return $from . ' day' . ($from === 1 ? '' : 's');
        }

        return $from . ' to ' . $to . ' days';
    }

    public function requiredDocumentList(): array
    {
        $value = trim((string) ($this->required_documents ?? ''));

        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(fn ($document) => trim((string) $document))
                ->filter()
                ->values()
                ->all();
        }

        return collect(preg_split('/\r\n|\r|\n/', $value, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($document) => trim((string) $document))
            ->filter()
            ->values()
            ->all();
    }

    public function governmentOffice()
    {
        return $this->belongsTo(GovernmentOffice::class, 'government_office_id', 'id');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id', 'id');
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_id', 'id');
    }

    public function feedback()
    {
        return $this->hasManyThrough(
            Feedback::class,
            ServiceRequest::class,
            'service_id',
            'service_request_id',
            'id',
            'id'
        );
    }
}
