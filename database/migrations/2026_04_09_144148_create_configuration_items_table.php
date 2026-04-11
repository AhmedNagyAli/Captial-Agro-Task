<?php

use App\Models\Configuration;
use App\Models\Option;
use App\Models\OptionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configuration_items', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Configuration::class, 'configuration_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->foreignIdFor(OptionGroup::class, 'option_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Option::class, 'option_id')
                ->constrained()
                ->cascadeOnDelete();

            // snapshot
            $table->string('option_group_name');
            $table->string('option_name');

            $table->decimal('price', 10, 2);

            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            $table->unique(
                ['configuration_id', 'option_group_id', 'option_id'],
                'config_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_items');
    }
};
