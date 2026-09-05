<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable()->after('status');
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex(['starts_at']);
            $table->dropColumn('starts_at');
        });
    }
};
