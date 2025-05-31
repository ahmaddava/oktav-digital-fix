<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ProductionItem::class, 'category_id');
    }

    public function activeItems(): HasMany
    {
        return $this->hasMany(ProductionItem::class, 'category_id')->where('is_active', true);
    }
}