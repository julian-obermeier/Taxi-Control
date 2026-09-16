<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feature extends Model
{
    protected $fillable = ['key', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(SaasPackage::class, 'feature_saas_package')
            ->withPivot(['enabled', 'limit_value']);
    }
}
