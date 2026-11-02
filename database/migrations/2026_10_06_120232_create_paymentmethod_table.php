<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Global master list of available payment channels (GCASH, MAYA, GOTYME, etc.)
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique(); // e.g. 'gcash', 'maya', 'gotyme'
            $table->string('name');                   // e.g. 'GCash', 'Maya', 'GoTyme'
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true); // System-wide availability
            $table->timestamps();
        });

        // Organization-specific payment method configuration
        Schema::create('organization_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->string('account_name');          // Same account name for all numbers in this method
            $table->json('account_numbers');         // ['09171234567', '09181234567']
            $table->boolean('is_enabled')->default(true); // Enabled / Disabled toggle
            $table->timestamps();

            // Prevent duplicate configuration of the same payment method for an organization
            $table->unique(['organization_id', 'payment_method_id'], 'org_pm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_payment_methods');
        Schema::dropIfExists('payment_methods');
    }
};
