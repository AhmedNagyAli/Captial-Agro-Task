<?php

use App\Models\Option;
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
        Schema::create('option_exclusions', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Option::class, 'option_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Option::class, 'excluded_option_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['option_id', 'excluded_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_exclusions');
    }
};
