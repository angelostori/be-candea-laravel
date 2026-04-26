<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // GET /api/products
    public function index(Request $request)
    {
        $query = Product::query();

        // PROMO FILTER
        if ($request->promo === 'y') {
            $query->whereColumn('initial_price', '!=', 'actual_price');
        }

        // SORTING
        $sortBy = $request->sortBy ?? 'name';
        $order = $request->order ?? 'asc';

        switch ($sortBy) {
            case 'price':
                $query->orderBy('actual_price', $order);
                break;

            case 'recent':
                $query->orderBy('created_at', $order);
                break;

            default:
                $query->orderBy('name', $order);
                break;
        }

        $products = $query->with('categories')->get();

        // SEARCH FILTER (LIKE Express q)
        if ($request->q) {
            $products = $products->filter(function ($item) use ($request) {
                return str_contains(
                    strtolower($item->name),
                    strtolower($request->q)
                );
            })->values();
        }

        // CATEGORY FILTER
        if ($request->category) {
            $cat = strtolower($request->category);

            $products = $products->filter(function ($product) use ($cat, $request) {
                return $product->categories->contains(function ($c) use ($cat, $request) {
                    return $c->id == $request->category ||
                        strtolower($c->name) === $cat;
                });
            })->values();
        }

        return response()->json($products);
    }

    // GET /api/products/{slug}
    public function show($slug)
    {
        $product = Product::with('categories')
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json([
                'error' => 404,
                'message' => 'Product Not Found'
            ], 404);
        }

        return response()->json($product);
    }

    // GET /api/products/popular
    public function showByNumberSold()
    {
        $products = Product::selectRaw('
                SUM(order_product.quantity) as total_sold,
                products.*
            ')
            ->join('order_product', 'products.id', '=', 'order_product.product_id')
            ->groupBy('products.id')
            ->orderByDesc('total_sold')
            ->with('categories')
            ->get();

        return response()->json($products);
    }
}
