<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // migration A: fix the cascade bug
        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropForeign(['variant_value_id']);
            $table->foreign('variant_value_id')
                ->references('id')->on('variant_values')
                ->restrictOnDelete();
        });

        // migration B: add sort_order
        Schema::table('variant_values', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('value');
        });

        // migration C: add soft delete for Category table
        Schema::table('categories', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variant_values', function (Blueprint $table) {
            $table->dropForeign(['variant_value_id']);
            $table->foreign('variant_value_id')
                ->references('id')->on('variant_values')
                ->cascadeOnDelete();
        });

        Schema::table('variant_values', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
