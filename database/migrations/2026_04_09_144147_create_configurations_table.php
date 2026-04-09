<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Product::class, 'product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('session_id')->nullable()->index();
            $table->string('token')->unique();

            $table->decimal('total_price', 10, 2)->default(0);

            $table->foreignIdFor(Order::class, 'order_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
