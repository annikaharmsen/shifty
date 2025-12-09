<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('template_shifts', function (Blueprint $table) {
            $table->integer('volume_rating')->nullable()->after('is_on_call');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->integer('volume_rating')->nullable()->after('is_on_call');
        });
    }

    public function down(): void
    {
        Schema::table('template_shifts', function (Blueprint $table) {
            $table->dropColumn('volume_rating');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('volume_rating');
        });
    }
};
