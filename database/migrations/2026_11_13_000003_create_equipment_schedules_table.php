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
        Schema::create('equipment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->cascadeOnDelete();
            $table->foreignId('item_unit_id')->nullable()->constrained('item_units')->cascadeOnDelete();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time')->index();
            $table->string('type')->default('maintenance')->index();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_schedules');
    }
};
