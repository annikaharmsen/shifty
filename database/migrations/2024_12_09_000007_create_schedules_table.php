<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->constrained()->cascadeOnDelete();
            $table->date('week_start_date');
            $table->softDeletes();
            $table->timestamps();

            $table->index('establishment_id');
            $table->unique(['establishment_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
