<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSettings\StoreVariantTypeRequest;
use App\Http\Requests\ProductSettings\UpdateVariantTypeRequest;
use App\Models\VariantType;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class VariantTypeController extends Controller
{
    public function index()
    {
        return Inertia::render('productSettings/Variants', [
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreVariantTypeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $type = VariantType::create(['name' => $request->validated('name')]);

            $type->values()->createMany(
                collect($request->validated('values'))
                    ->map(fn ($value) => ['value' => $value])
                    ->all()
            );
        });

        return back()->with('success', 'Variant type created.');
    }

    public function update(UpdateVariantTypeRequest $request, VariantType $variantType)
    {
        $variantType->update($request->validated());

        return back()->with('success', 'Variant type updated.');
    }

    public function destroy(VariantType $variantType)
    {
        $variantType->delete();

        return back()->with('success', 'Variant type deleted.');
    }
}
