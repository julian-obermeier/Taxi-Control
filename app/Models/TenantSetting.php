<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['key', 'value', 'is_secret'];

    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }
}
