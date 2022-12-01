<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'type_delivery',
        'address',
        'flat',
        'type_pay',
        'odd_money',
        'person',
        'comment',
        'price',
        'user'
    ];
}
