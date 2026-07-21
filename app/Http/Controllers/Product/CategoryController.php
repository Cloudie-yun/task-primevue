<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSettings\StoreCategoryRequest;
use App\Http\Requests\ProductSettings\UpdateCategoryRequest;
use Illuminate\Http\Request;
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
        $trashed = Category::onlyTrashed()->where('name', $request->validated('name'))->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($request->validated()); // in case other fields changed too

            return back()->with('success', 'Category restored.');
        }
        Category::create($request->validated());

        return back()->with('success', 'Category created.');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category, Request $request )
    {
        $force = $request->boolean('force');
        $products = $category->products()->get(['id', 'name']);
        if ($products->isNotEmpty() && !$force) {
            return back()->withErrors([
                'affected_products' => $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->toJson(),
                'delete' => 'This category cannot be deleted because it is currently used by products.',
            ]);
        }

        if ($force && $products->isNotEmpty()) {
            $category->products()->update(['category_id' => null]);
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }
}
