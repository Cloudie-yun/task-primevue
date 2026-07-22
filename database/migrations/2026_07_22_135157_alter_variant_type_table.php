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
        // migration
        Schema::table('variant_types', function (Blueprint $table) {
            $table->boolean('generates_variants')->default(true)->after('name');
        });

        // new pivot migration
        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['product_id', 'variant_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_types', function (Blueprint $table) {
            $table->dropColumn('generates_variants');
        });

        Schema::dropIfExists('product_attribute_value');
    }
};
