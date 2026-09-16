<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStop extends Model
{
    use BelongsToTenant;

    protected $fillable = ['trip_id', 'sequence', 'stop_type', 'address', 'lat', 'lng', 'contact_name', 'contact_phone', 'planned_at', 'arrived_at', 'departed_at', 'waiting_seconds', 'passenger_boarding', 'passenger_alighting', 'notes'];

    protected function casts(): array
    {
        return ['planned_at' => 'datetime', 'arrived_at' => 'datetime', 'departed_at' => 'datetime', 'waiting_seconds' => 'integer', 'passenger_boarding' => 'boolean', 'passenger_alighting' => 'boolean'];
    }

    public function trip(): BelongsTo { return $this->belongsTo(Trip::class); }
}
