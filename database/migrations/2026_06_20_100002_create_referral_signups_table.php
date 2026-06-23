<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks one "refer a friend" relationship: who referred whom, and whether
     * that referral has become a rewarded (qualifying) visit yet. Distinct from
     * the clinical `referrals` table (specialist referrals).
     */
    public function up(): void
    {
        Schema::create('referral_signups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            // A given user can only ever be referred once.
            $table->foreignId('referred_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code'); // the code used at sign-up (snapshot)
            $table->string('status')->default('pending')->index();
            $table->foreignId('qualifying_appointment_id')->nullable()
                ->constrained('appointments')->nullOnDelete();
            $table->timestamp('qualified_at')->nullable();
            // Points actually awarded when it qualified (snapshot of the config).
            $table->unsignedInteger('referrer_points')->default(0);
            $table->unsignedInteger('welcome_points')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_signups');
    }
};
