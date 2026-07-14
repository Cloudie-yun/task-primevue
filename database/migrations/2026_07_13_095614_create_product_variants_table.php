<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('variant_types', function (Blueprint $table) {
            $table->id();

            $table->string('name')
                ->unique();

            $table->timestamps();
        });

        Schema::create('variant_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('variant_type_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('value');

            $table->timestamps();

            $table->unique(
                [
                    'variant_type_id',
                    'value'
                ],
                'variant_type_value_unique'
            );
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')
                ->unique();

            $table->decimal('additional_price', 10, 2)->default(0);

            $table->integer('stock')
                ->default(0);

            $table->timestamps();
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('variant_value_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'product_variant_id',
                    'variant_value_id'
                ],
                'variant_value_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('variant_values');
        Schema::dropIfExists('variant_types');
    }
};
