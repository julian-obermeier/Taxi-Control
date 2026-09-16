<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    use BelongsToTenant;

    protected $fillable = ['webhook_id', 'event', 'status', 'attempt', 'http_status', 'payload', 'response_excerpt', 'next_retry_at', 'delivered_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'next_retry_at' => 'datetime', 'delivered_at' => 'datetime'];
    }
}
