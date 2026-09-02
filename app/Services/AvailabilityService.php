<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\ExternalCalendarEvent;

class AvailabilityService
{
    public function isAvailable(string $checkin, string $checkout): bool
    {
        return ! $this->hasBookingConflict($checkin, $checkout)
            && ! $this->hasPendingRequestConflict($checkin, $checkout)
            && ! $this->hasManualBlockConflict($checkin, $checkout)
            && ! $this->hasExternalCalendarConflict($checkin, $checkout);
    }

    private function hasBookingConflict(string $checkin, string $checkout): bool
    {
        return Booking::query()
            ->whereNull('canceled_at')
            ->whereDate('checkin', '<', $checkout)
            ->whereDate('checkout', '>', $checkin)
            ->exists();
    }

    private function hasPendingRequestConflict(string $checkin, string $checkout): bool
    {
        return AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNotNull('booking_request_id')
            ->whereDate('start_date', '<', $checkout)
            ->whereDate('end_date', '>', $checkin)
            ->exists();
    }

    private function hasManualBlockConflict(string $checkin, string $checkout): bool
    {
        return AvailabilityBlock::query()
            ->whereNull('booking_id')
            ->whereNull('booking_request_id')
            ->whereIn('reason', ['owner', 'maintenance'])
            ->whereDate('start_date', '<', $checkout)
            ->whereDate('end_date', '>=', $checkin)
            ->exists();
    }

    private function hasExternalCalendarConflict(string $checkin, string $checkout): bool
    {
        return ExternalCalendarEvent::query()
            ->whereHas('provider', fn ($query) => $query->availableForAvailability())
            ->whereDate('start_date', '<', $checkout)
            ->whereDate('end_date', '>', $checkin)
            ->exists();
    }
}
