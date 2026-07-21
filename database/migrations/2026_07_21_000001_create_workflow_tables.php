<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Workflows table - stores each automation workflow
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('trigger_event')->default('enrollment.submitted');
            $table->json('trigger_conditions')->nullable(); // filter conditions
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Workflow nodes - each draggable node on the canvas
        Schema::create('workflow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('node_key')->index(); // unique key within workflow e.g. "node_1"
            $table->string('type'); // trigger, send_email, change_status, condition, delay, notify_admin
            $table->string('label');
            $table->json('config')->nullable(); // node-specific config (email template, status value, etc.)
            $table->float('position_x')->default(0);
            $table->float('position_y')->default(0);
            $table->timestamps();
        });

        // Workflow edges - connections between nodes
        Schema::create('workflow_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('edge_key')->index(); // e.g. "edge_1"
            $table->string('source_node_key');
            $table->string('target_node_key');
            $table->string('condition_value')->nullable(); // for condition nodes: "yes" or "no"
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Workflow runs - one run per applicant per workflow trigger
        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('applicant_id')->nullable();
            $table->string('current_node_key')->nullable();
            $table->string('status')->default('running'); // running, completed, failed, stopped
            $table->json('context')->nullable(); // runtime variables
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'status']);
            $table->index('applicant_id');
        });

        // Workflow run logs - log of each node execution
        Schema::create('workflow_run_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained('workflow_runs')->cascadeOnDelete();
            $table->string('node_key');
            $table->string('node_type');
            $table->string('status'); // success, failed, skipped
            $table->text('message')->nullable();
            $table->json('output')->nullable();
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_run_logs');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_edges');
        Schema::dropIfExists('workflow_nodes');
        Schema::dropIfExists('workflows');
    }
};
