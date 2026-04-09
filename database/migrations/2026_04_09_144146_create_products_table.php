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
        Schema::create('products', function (Blueprint $table) {
    $table->id();

    // identity
    $table->string('name');
    $table->string('slug')->unique();

    // content
    $table->text('description')->nullable();
    $table->longText('details')->nullable();

    // pricing
    $table->decimal('base_price', 10, 2)->default(0);
    $table->decimal('sale_price', 10, 2)->nullable();
    // media
    $table->string('image')->nullable();

    // status
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);

    // SEO
    $table->string('meta_title')->nullable();
    $table->string('meta_description')->nullable();

    $table->timestamps();

    $table->index('is_active');
    $table->index('slug');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
