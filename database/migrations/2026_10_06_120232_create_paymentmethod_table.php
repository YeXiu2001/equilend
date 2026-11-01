<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique();
            $table->string('name');
            $table->string('account_no');
            $table->string('account_name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('org_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organization')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paymentmethod');
    }
};
