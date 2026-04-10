<?php

use App\Models\OptionGroup;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_option_group', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Product::class ,'product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(OptionGroup::class, 'option_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unique(['product_id', 'option_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_group');
    }
};
