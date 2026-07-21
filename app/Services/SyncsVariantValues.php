<?php

namespace App\Services;

use App\Models\VariantType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncsVariantValues
{
    public function sync(VariantType $variantType, array $rows, bool $force): void
    {
        $incomingIds = collect($rows)->pluck('id')->filter()->all();

        DB::transaction(function () use ($rows, $incomingIds, $variantType, $force) {
            $toRemove = $variantType->values()->whereNotIn('id', $incomingIds)->get();
            $blocked = collect();

            foreach ($toRemove as $value) {
                $variants = $value->productVariants()->get();

                if ($variants->isNotEmpty() && ! $force) {
                    $blocked->push([
                        'id' => $value->id,
                        'value' => $value->value,
                        'variant_count' => $variants->count(),
                        'skus' => $variants->pluck('sku')->all(),
                    ]);
                    continue;
                }

                $variants->each->delete();
                $value->delete();
            }

            if ($blocked->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'values' => $blocked->pluck('value')->implode(', ') . ' cannot be removed because they are used by existing product variants.',
                    'blocked_values' => $blocked->toJson(),
                ]);
            }

            foreach ($rows as $index => $row) {
                if (! empty($row['id'])) {
                    $variantType->values()->whereKey($row['id'])->update([
                        'value' => $row['value'],
                        'sort_order' => $index,
                    ]);
                } else {
                    $variantType->values()->create([
                        'value' => $row['value'],
                        'sort_order' => $index,
                    ]);
                }
            }
        });
    }
}