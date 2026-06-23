<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('endorsed_at')->nullable()->after('status');
            $table->foreignId('endorsed_by')->nullable()->after('endorsed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('billed_at')->nullable()->after('endorsed_by');
            $table->foreignId('billed_by')->nullable()->after('billed_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('billing_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('statement_no')->unique();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_statements');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('endorsed_by');
            $table->dropConstrainedForeignId('billed_by');
            $table->dropColumn(['endorsed_at', 'billed_at']);
        });
    }
};
