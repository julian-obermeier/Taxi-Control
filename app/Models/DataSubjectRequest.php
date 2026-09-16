<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DataSubjectRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = ['user_id', 'request_type', 'status', 'due_at', 'completed_at', 'notes'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'completed_at' => 'datetime'];
    }
}
