<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_data_sources', function (Blueprint $table): void {
            $table->string('id', 64)->primary();
            $table->string('name', 160);
            $table->string('source_type', 40);
            $table->string('access_mode', 40);
            $table->text('description');
            $table->timestampsTz();
        });

        Schema::create('policy_data_source_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('source_id', 64);
            $table->string('version', 128);
            $table->string('policy_version', 32);
            $table->timestampsTz();

            $table->foreign('source_id')->references('id')->on('policy_data_sources')->cascadeOnDelete();
            $table->unique(['source_id', 'version']);
        });

        Schema::create('policy_permission_evidence', function (Blueprint $table): void {
            $table->string('id', 96)->primary();
            $table->foreignId('source_version_id')->constrained('policy_data_source_versions')->cascadeOnDelete();
            $table->text('evidence_url');
            $table->timestampTz('retrieved_at');
            $table->timestampTz('effective_from');
            $table->timestampTz('effective_until')->nullable();
            $table->string('permission_status', 32);
            $table->boolean('attribution_required')->default(false);
            $table->text('attribution_notice')->nullable();
            $table->text('summary');
            $table->string('reviewer_role', 80);
            $table->timestampsTz();
        });

        Schema::create('policy_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_version_id')->constrained('policy_data_source_versions')->cascadeOnDelete();
            $table->string('capability', 40);
            $table->string('operation', 192);
            $table->string('decision', 32);
            $table->string('reason', 64);
            $table->json('required_conditions');
            $table->text('explanation');
            $table->string('policy_version', 32);
            $table->boolean('enabled')->default(true);
            $table->timestampsTz();

            $table->unique(['source_version_id', 'capability', 'operation']);
        });

        Schema::create('policy_kill_switches', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 32);
            $table->string('source_id', 64)->nullable();
            $table->string('capability', 40)->nullable();
            $table->boolean('active')->default(false);
            $table->text('reason');
            $table->timestampTz('activated_at')->nullable();
            $table->timestampsTz();

            $table->index(['active', 'scope']);
            $table->foreign('source_id')->references('id')->on('policy_data_sources')->nullOnDelete();
        });

        Schema::create('policy_decision_audits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('source_id', 64);
            $table->string('source_version', 128);
            $table->string('capability', 40);
            $table->string('operation', 192);
            $table->string('decision', 32);
            $table->string('reason', 64);
            $table->string('policy_version', 32);
            $table->json('evidence_ids');
            $table->json('condition_flags');
            $table->timestampTz('evaluated_at');
            $table->string('actor_type', 32);
            $table->timestampsTz();

            $table->index(['source_id', 'capability', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_decision_audits');
        Schema::dropIfExists('policy_kill_switches');
        Schema::dropIfExists('policy_rules');
        Schema::dropIfExists('policy_permission_evidence');
        Schema::dropIfExists('policy_data_source_versions');
        Schema::dropIfExists('policy_data_sources');
    }
};
