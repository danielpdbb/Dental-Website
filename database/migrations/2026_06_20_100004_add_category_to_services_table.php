<?php

use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('category')->default(ServiceCategory::Other->value)->after('name')->index();
        });

        // Backfill sensible categories for any services already seeded.
        Service::withTrashed()->get()->each(function (Service $service) {
            $service->update(['category' => ServiceCategory::guessFromName($service->name)->value]);
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
