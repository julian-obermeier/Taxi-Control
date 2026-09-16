<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripType extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'slug', 'default_priority', 'is_active'];
    protected function casts(): array { return ['default_priority' => 'integer', 'is_active' => 'boolean']; }
    public function trips(): HasMany { return $this->hasMany(Trip::class); }
}
