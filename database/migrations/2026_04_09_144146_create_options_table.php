<?php

use App\Models\OptionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('options', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(OptionGroup::class, 'option_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');

            // price modifier 
            $table->decimal('price', 10, 2)->default(0);

            $table->unsignedInteger('stock')->default(0);

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
