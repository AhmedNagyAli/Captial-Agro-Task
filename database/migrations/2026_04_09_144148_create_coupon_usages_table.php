<?php

use App\Models\Coupon;
use App\Models\Order;
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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Coupon::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(Order::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['coupon_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
