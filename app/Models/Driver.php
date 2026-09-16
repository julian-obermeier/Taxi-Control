<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'employee_number', 'display_name', 'phone', 'status', 'qualifications', 'is_active'];

    protected function casts(): array
    {
        return ['qualifications' => 'array', 'is_active' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function trips(): HasMany { return $this->hasMany(Trip::class); }
    public function locations(): HasMany { return $this->hasMany(DriverLocation::class); }
}
