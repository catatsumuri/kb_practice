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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_namespace_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->string('visibility')->default('private')->index();
            $table->string('document_type')->default('original')->index();
            $table->string('source_title')->nullable();
            $table->string('source_url')->nullable();
            $table->string('source_author')->nullable();
            $table->longText('source_content')->nullable();
            $table->timestamps();

            $table->unique(['document_namespace_id', 'path']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
