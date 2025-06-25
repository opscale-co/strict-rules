<?php

namespace Opscale\Http\Controllers;

use Illuminate\Http\Request;
use Opscale\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Fetch products from the database
        $products = Product::all();

        // Return the products as a JSON response
        return response()->json($products);
    }

    public function show($id)
    {
        // Find a product by its ID
        $product = Product::findOrFail($id);

        // Return the product as a JSON response
        return response()->json($product);
    }
}