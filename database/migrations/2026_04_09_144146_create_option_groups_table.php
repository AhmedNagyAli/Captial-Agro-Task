<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('single'); // single, multiple, text, number, file, color
            $table->integer('min_selections')->default(1);
            $table->integer('max_selections')->nullable(); // null = unlimited
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable(); // Custom validation rules
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('option_groups');
    }
};
