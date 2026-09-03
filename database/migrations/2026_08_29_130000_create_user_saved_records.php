<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_saved_builds', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('build_id');
            $table->string('label', 120)->nullable();
            $table->timestampTz('created_at');
            $table->primary(['user_id', 'build_id']);
            $table->foreign('build_id')->references('id')->on('builds')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_saved_analyses', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('analysis_id');
            $table->timestampTz('created_at');
            $table->primary(['user_id', 'analysis_id']);
            $table->foreign('analysis_id')->references('id')->on('analyses')->cascadeOnDelete();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_saved_analyses');
        Schema::dropIfExists('user_saved_builds');
    }
};
