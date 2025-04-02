<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = [
        'nama_customer',
        'email_customer',
        'nomor_customer',
        'alamat_customer',
    ];
    //
}
