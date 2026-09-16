<?php

namespace App\Models;

use App\Enums\TripStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'order_number', 'trip_type_id', 'driver_id', 'vehicle_id', 'status', 'dispatch_mode', 'priority',
        'passenger_name', 'passenger_phone', 'passenger_count', 'vehicle_class', 'requirements',
        'pickup_address', 'pickup_lat', 'pickup_lng', 'destination_address', 'destination_lat', 'destination_lng',
        'requested_at', 'scheduled_at', 'assigned_at', 'accepted_at', 'picked_up_at', 'completed_at', 'cancelled_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TripStatus::class,
            'requirements' => 'array',
            'priority' => 'integer',
            'passenger_count' => 'integer',
            'requested_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function type(): BelongsTo { return $this->belongsTo(TripType::class, 'trip_type_id'); }
    public function driver(): BelongsTo { return $this->belongsTo(Driver::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function stops(): HasMany { return $this->hasMany(TripStop::class)->orderBy('sequence'); }
    public function stateEvents(): HasMany { return $this->hasMany(TripStateEvent::class)->orderBy('id'); }
}
