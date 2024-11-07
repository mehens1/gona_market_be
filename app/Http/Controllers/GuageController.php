<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Models\Guage;

class GuageController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $categories = Guage::all();
        return $this->successResponse($categories, 'Guage Fetched Successfully!', 200);
    }
}
