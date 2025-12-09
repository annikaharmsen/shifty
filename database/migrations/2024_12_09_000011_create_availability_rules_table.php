<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available');
            $table->dateTime('start_datetime');
            $table->integer('duration'); // stored as seconds
            $table->string('frequency')->nullable(); // e.g., "1 week", "1 day"
            $table->dateTime('termination_datetime')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
