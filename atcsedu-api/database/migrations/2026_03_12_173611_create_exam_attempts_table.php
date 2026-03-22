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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('submitted');
            $table->unsignedInteger('objective_score')->default(0);
            $table->unsignedInteger('objective_total')->default(0);
            $table->unsignedInteger('theory_score')->default(0);
            $table->unsignedInteger('theory_total')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
