<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->unique()->after('statement_no');
            $table->decimal('discount', 10, 2)->default(0)->after('subtotal');
            $table->timestamp('paid_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'discount', 'paid_at']);
        });
    }
};
