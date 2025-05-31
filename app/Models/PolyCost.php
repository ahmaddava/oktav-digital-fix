<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolyCost extends Model
{
    protected $fillable = ['dimension', 'cost'];

    public static function getByDimension($dimension)
    {
        return self::where('dimension', $dimension)->first();
    }
}