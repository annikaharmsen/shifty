<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->dateTime('start_datetime');
            $table->integer('duration'); // stored as seconds
            $table->boolean('is_on_call')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('schedule_id');
            $table->index('role_id');
            $table->index('assignee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
