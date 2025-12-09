<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
