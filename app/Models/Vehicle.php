<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToTenant;

    protected $fillable = ['fleet_number', 'license_plate', 'vehicle_class', 'seats', 'equipment', 'status', 'is_active'];

    protected function casts(): array
    {
        return ['equipment' => 'array', 'is_active' => 'boolean', 'seats' => 'integer'];
    }

    public function trips(): HasMany { return $this->hasMany(Trip::class); }
    public function locations(): HasMany { return $this->hasMany(DriverLocation::class); }
}
