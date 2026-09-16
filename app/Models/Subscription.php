<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'saas_package_id', 'status', 'starts_at', 'trial_ends_at', 'renews_at', 'ends_at', 'billing_cycle', 'external_reference',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
