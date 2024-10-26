<?php

namespace App\Http\Controllers;
use App\Http\Resources\ProductResource;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index() {
        $products = Product::with(['guage', 'category', 'added_by.userDetail'])->get();
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with('guage')->findOrFail($id);
        return new ProductResource($product);
    }
}
