<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function show($id)
    {
        $order = Order::with('products')->find($id);

        if (!$order) {
            return response()->json([
                'error' => 404,
                'message' => 'Order Not Found'
            ], 404);
        }

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone_number' => 'required',
            'city' => 'required',
            'province' => 'required',
            'street' => 'required',
            'zip_code' => 'required',
            'products' => 'required|array|min:1'
        ]);

        $total = 0;

        foreach ($request->products as $item) {
            $product = Product::find($item['id']);
            if ($product) {
                $total += $product->actual_price * $item['quantity'];
            }
        }

        if ($request->discount_code_id) {
            $discount = DiscountCode::find($request->discount_code_id);

            if (!$discount) {
                return response()->json(['message' => 'Wrong discount code'], 400);
            }

            $total -= ($total * $discount->value / 100);
        }

        $freeShipping = false;

        if ($total > 90) {
            $freeShipping = true;
        } else {
            $total += 5;
        }

        $order = Order::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'city' => $request->city,
            'province' => $request->province,
            'street' => $request->street,
            'street_number' => $request->street_number,
            'zip_code' => $request->zip_code,
            'total_amount' => $total,
            'free_shipping' => $freeShipping,
            'shipment_code' => strtoupper(Str::random(10)),
            'discount_code_id' => $request->discount_code_id
        ]);

        foreach ($request->products as $item) {
            $order->products()->attach($item['id'], [
                'quantity' => $item['quantity']
            ]);

            // decrement stock
            Product::where('id', $item['id'])
                ->decrement('available_quantity', $item['quantity']);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'shipment_code' => $order->shipment_code
        ], 201);
    }
}
