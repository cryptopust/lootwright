<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('build_artifacts', function (Blueprint $table): void {
            $table->longText('raw_contents_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('build_artifacts', function (Blueprint $table): void {
            $table->dropColumn('raw_contents_encrypted');
        });
    }
};
