<?php

namespace App\Services;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\TripStateEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TripStateMachine
{
    private const TRANSITIONS = [
        'draft' => ['new', 'cancelled'],
        'new' => ['reserved', 'ready_for_dispatch', 'searching_driver', 'cancelled', 'manual_review'],
        'reserved' => ['ready_for_dispatch', 'cancelled', 'manual_review'],
        'ready_for_dispatch' => ['searching_driver', 'assigned', 'cancelled', 'manual_review'],
        'searching_driver' => ['offer_sent', 'assigned', 'cancelled', 'manual_review', 'technical_issue'],
        'offer_sent' => ['accepted', 'searching_driver', 'assigned', 'cancelled', 'manual_review'],
        'assigned' => ['accepted', 'en_route', 'searching_driver', 'cancelled', 'manual_review'],
        'accepted' => ['en_route', 'cancelled', 'technical_issue'],
        'en_route' => ['at_pickup', 'cancelled', 'technical_issue'],
        'at_pickup' => ['waiting', 'passenger_on_board', 'no_show', 'cancelled', 'technical_issue'],
        'waiting' => ['passenger_on_board', 'no_show', 'cancelled', 'technical_issue'],
        'passenger_on_board' => ['in_progress', 'technical_issue'],
        'in_progress' => ['at_stop', 'at_destination', 'technical_issue'],
        'at_stop' => ['in_progress', 'at_destination', 'technical_issue'],
        'at_destination' => ['finishing', 'completed', 'manual_review'],
        'finishing' => ['completed', 'manual_review'],
        'technical_issue' => ['manual_review', 'searching_driver', 'en_route', 'in_progress', 'cancelled'],
        'manual_review' => ['new', 'ready_for_dispatch', 'searching_driver', 'finishing', 'completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    public function can(TripStatus $from, TripStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    public function transition(
        Trip $trip,
        TripStatus $to,
        ?int $userId = null,
        string $source = 'web',
        ?string $reason = null,
        array $metadata = [],
    ): Trip {
        return DB::transaction(function () use ($trip, $to, $userId, $source, $reason, $metadata): Trip {
            $locked = Trip::query()->whereKey($trip->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->status;

            if (! $from instanceof TripStatus || ! $this->can($from, $to)) {
                throw ValidationException::withMessages([
                    'status' => sprintf('Statuswechsel von "%s" nach "%s" ist nicht zulässig.', $from instanceof TripStatus ? $from->label() : (string) $from, $to->label()),
                ]);
            }

            $changes = ['status' => $to];
            if ($to === TripStatus::Assigned) $changes['assigned_at'] = now();
            if ($to === TripStatus::Accepted) $changes['accepted_at'] = now();
            if ($to === TripStatus::PassengerOnBoard) $changes['picked_up_at'] = now();
            if ($to === TripStatus::Completed) $changes['completed_at'] = now();
            if ($to === TripStatus::Cancelled) $changes['cancelled_at'] = now();

            $locked->forceFill($changes)->save();

            TripStateEvent::query()->create([
                'trip_id' => $locked->id,
                'user_id' => $userId,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'source' => $source,
                'reason' => $reason,
                'metadata' => $metadata ?: null,
                'created_at' => now(),
            ]);

            return $locked->fresh();
        }, 3);
    }
}
