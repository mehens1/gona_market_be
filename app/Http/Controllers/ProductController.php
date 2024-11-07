<?php

namespace App\Http\Controllers;
use App\Http\Resources\ProductResource;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponseTrait;
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function index() {
        $products = Product::with(['guage', 'category', 'added_by.userDetail'])->paginate(10);
        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with('guage')->findOrFail($id);
        return new ProductResource($product);
    }

    public function myProducts(Request $request) {
        $user = $request->user()->id;
        $products = Product::where('added_by', $user)->with(['guage', 'category', 'added_by.userDetail'])->get();
        return $this->successResponse([$products], 'User Store Products fetched successfully successfully!', 201);
    }

    public function uploadProduct(Request $request)
    {

        $request->validate([
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'product_category' => 'required|int',
            'product_guage' => 'int|required',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'product_description' => 'string|nullable',
            'product_quantity' => 'required|int',
        ]);

        try {
            return DB::transaction(function () use ($request) {

                $user = $request->user()->id;

                $cleanString = preg_replace('/[^A-Za-z0-9 ]/', '', $request->product_name);
                $title = str_replace(" ", "_", trim($cleanString));

                $file = $request->file('product_image');
                $folder = "product_images/$title";
                $uploadedFileUrl = $this->fileUploadService->uploadFile($file, $folder);

                $product = Product::create([
                    'title' => $request->product_name,
                    'price' => $request->product_price,
                    'description' => $request->product_description,
                    'image' => $uploadedFileUrl,
                    'qty_available' => $request->product_quantity,
                    'category' => $request->product_category,
                    'guage' => $request->product_guage,
                    'added_by' => $user,
                ]);

                return $this->successResponse($product, 'Product Uploaded Successfully!', 201);
            });

        } catch (\Throwable $th) {
            \Log::error('Product upload failed in uploadProduct', ['error' => $th->getMessage()]);
            return $this->errorResponse('Product upload failed!', $th->getMessage(), 500);
        }

    }
}
