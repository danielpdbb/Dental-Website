<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The rewards ledger. A user's balance is simply the SUM of `points` across
     * their rows (credits positive, redemptions/expiries negative), so the
     * balance can never drift from its history.
     */
    public function up(): void
    {
        Schema::create('reward_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->index();      // earned | welcome | adjusted | redeemed | expired
            $table->integer('points');            // signed: + credit, − debit
            $table->string('description')->nullable();

            // Optional links back to what caused the entry.
            $table->foreignId('referral_signup_id')->nullable()
                ->constrained('referral_signups')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()
                ->constrained('appointments')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()
                ->constrained('payments')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()
                ->constrained('users')->nullOnDelete();

            // For earned points: when they lapse if the account goes inactive.
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_transactions');
    }
};
