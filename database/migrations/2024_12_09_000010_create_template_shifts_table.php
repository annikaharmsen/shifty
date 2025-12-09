<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->integer('day_of_week'); // 1-7, Monday=1
            $table->time('start_time');
            $table->integer('duration'); // stored as seconds
            $table->boolean('is_on_call')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('schedule_template_id');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_shifts');
    }
};
