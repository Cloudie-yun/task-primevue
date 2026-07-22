<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\SyncsVariantValues;
use App\Http\Requests\ProductSettings\StoreVariantTypeRequest;
use App\Http\Requests\ProductSettings\UpdateVariantTypeRequest;
use App\Models\VariantType;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class VariantTypeController extends Controller
{
    public function __construct(private SyncsVariantValues $valueSyncer) {}

    public function index()
    {
        return Inertia::render('productSettings/Variants', [
            'variantTypes' => VariantType::with('values')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreVariantTypeRequest $request)
    {
        DB::transaction(function () use ($request) {
            $type = VariantType::create([
                'name' => $request->validated('name'),
                'generates_variants' => $request->boolean('generates_variants'),
            ]);

            $type->values()->createMany(
                collect($request->validated('values'))
                    ->values() 
                    ->map(fn ($value, $index) => [
                        'value' => $value,
                        'sort_order' => $index,
                    ])
                    ->all()
            );
        });

        return back()->with('success', 'Variant type created.');
    }

    public function update(UpdateVariantTypeRequest $request, VariantType $variantType)
    {
        DB::transaction(function () use ($request, $variantType) {
            $variantType->update([
                'name' => $request->validated('name'),
                'generates_variants' => $request->boolean('generates_variants'),
            ]);
            $this->valueSyncer->sync($variantType, $request->validated('values'), $request->boolean('force'));
        });

        return back()->with('success', 'Variant type updated.');
    }

    public function destroy(VariantType $variantType)
    {
        $isInUse = $variantType->values()
            ->whereHas('productVariants')
            ->exists();

        if ($isInUse) {
            return back()->withErrors([
                'delete' => 'This variant type cannot be deleted because one or more of its values are currently used by products.',
            ]);
        }

        $variantType->delete();

        return back()->with('success', 'Variant type deleted.');
    }
}
