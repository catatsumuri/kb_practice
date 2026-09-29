<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // source_url is often a fetch-only variant of the real page
            // (e.g. Mintlify's "/introduction.md" for "/introduction"),
            // so it can't double as the URL a reader would actually want
            // to visit. canonical_url holds that address instead.
            $table->string('canonical_url')->nullable()->after('source_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('canonical_url');
        });
    }
};
