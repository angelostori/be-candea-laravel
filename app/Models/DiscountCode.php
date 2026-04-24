<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
