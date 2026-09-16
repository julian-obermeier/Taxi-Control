<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLocation extends Model
{
    use BelongsToTenant;

    protected $fillable = ['driver_id', 'vehicle_id', 'lat', 'lng', 'accuracy_m', 'speed_kmh', 'heading', 'recorded_at'];
    protected function casts(): array { return ['recorded_at' => 'datetime']; }
    public function driver(): BelongsTo { return $this->belongsTo(Driver::class); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
}
