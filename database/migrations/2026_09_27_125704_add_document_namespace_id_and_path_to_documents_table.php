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
            $table->foreignId('document_namespace_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('path')->nullable()->after('document_namespace_id');
            $table->unique(['document_namespace_id', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['document_namespace_id', 'path']);
            $table->dropConstrainedForeignId('document_namespace_id');
            $table->dropColumn('path');
        });
    }
};
