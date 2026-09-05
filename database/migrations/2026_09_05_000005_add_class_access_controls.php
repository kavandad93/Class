<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('classes', 'allow_guest')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->boolean('allow_guest')->default(false)->after('status');
            });
        }

        if (!Schema::hasTable('class_allowed_users')) {
            Schema::create('class_allowed_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('email', 255);
                $table->timestamps();
                $table->unique(['class_id', 'user_id']);
                $table->index(['class_id', 'email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_allowed_users');
        if (Schema::hasColumn('classes', 'allow_guest')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('allow_guest');
            });
        }
    }
};
