<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referral-letter fields: the clinic fills a short form and the system generates a
 * formal referral letter (PDF) the patient can view/download once issued.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->string('referred_to_name')->nullable()->after('notes');     // e.g. "Dr. Maria Santos"
            $table->string('referred_to_clinic')->nullable()->after('referred_to_name');
            $table->string('referred_to_address')->nullable()->after('referred_to_clinic');
            $table->text('letter_notes')->nullable()->after('referred_to_address'); // clinical summary in the letter
            $table->string('letter_no')->nullable()->after('letter_notes');
            $table->timestamp('letter_issued_at')->nullable()->after('letter_no');
            $table->foreignId('letter_issued_by')->nullable()->after('letter_issued_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('letter_issued_by');
            $table->dropColumn(['referred_to_name', 'referred_to_clinic', 'referred_to_address', 'letter_notes', 'letter_no', 'letter_issued_at']);
        });
    }
};
