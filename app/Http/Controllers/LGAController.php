<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LocalGovernmentArea;

class LGAController extends Controller
{
    public function index()
    {
        return LocalGovernmentArea::all();
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        return LocalGovernmentArea::where('id', $id);
    }

    public function state(string $state)
    {
        return LocalGovernmentArea::where('state', $state)->get();
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
