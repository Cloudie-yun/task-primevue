<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSettings\StoreCategoryRequest;
use App\Models\Category;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('productSettings/Categories', [
            'categories' => Category::query()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return back()->with('success', 'Category created.');
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
