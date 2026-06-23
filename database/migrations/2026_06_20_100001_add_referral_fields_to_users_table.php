<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Each patient's own shareable "refer a friend" code.
            $table->string('referral_code')->nullable()->unique()->after('avatar_path');
            // Who referred this user (nullable — most accounts weren't referred).
            $table->foreignId('referred_by_id')->nullable()->after('referral_code')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_id');
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
