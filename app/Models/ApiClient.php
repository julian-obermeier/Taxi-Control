<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use BelongsToTenant;

    protected $fillable = ['name', 'public_key', 'secret_hash', 'scopes', 'last_used_at', 'expires_at', 'is_active'];
    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
