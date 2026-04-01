<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = [
        'name',
        'use_clicks',
    ];

    protected $casts = [
        'use_clicks' => 'boolean',
    ];

    public function productions(): HasMany
    {
        return $this->hasMany(Production::class);
    }
}
