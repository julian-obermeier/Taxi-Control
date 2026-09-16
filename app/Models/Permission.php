<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'key', 'label', 'module', 'action', 'data_scope', 'sensitive_field', 'description',
    ];
}
