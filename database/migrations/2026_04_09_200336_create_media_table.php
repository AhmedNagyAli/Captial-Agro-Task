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
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            // polymorphic relation
            $table->morphs('mediable'); // mediable_id, mediable_type

            $table->string('disk')->default('public');
            $table->string('path');

            $table->enum('type', ['image', 'video', 'file'])->default('image');

            $table->unsignedInteger('sort_order')->default(0);

            $table->json('meta')->nullable(); // width, height, size, etc.

            $table->timestamps();

            $table->index(['mediable_id', 'mediable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
