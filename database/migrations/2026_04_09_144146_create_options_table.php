<?php

use App\Models\OptionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(OptionGroup::class, 'option_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('price_type', ['fixed', 'percentage', 'formula'])->default('fixed');
            $table->decimal('price_value', 10, 2)->default(0);
            $table->string('sku')->nullable(); // For tracking
            $table->integer('stock')->default(0);
            $table->json('metadata')->nullable(); // Store any custom data (color codes, file types, etc.)
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['option_group_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('options');
    }
};
