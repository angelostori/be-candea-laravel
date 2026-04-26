<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('quantity');
    }

    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class);
    }
}
