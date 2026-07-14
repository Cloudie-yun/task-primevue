<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VariantTypeController extends Controller
{
    public function index()
    {
        return Inertia::render(
            'productSettings/Variants'
        );
    }
}
