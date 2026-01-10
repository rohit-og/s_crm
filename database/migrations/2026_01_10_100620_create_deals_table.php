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
        Schema::create('deals', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('id', true);
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('client_id');
            $table->integer('pipeline_id');
            $table->integer('pipeline_stage_id');
            $table->decimal('value', 15, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->date('expected_close_date')->nullable();
            $table->date('actual_close_date')->nullable();
            $table->integer('probability')->default(0)->comment('Percentage 0-100');
            $table->enum('status', ['open', 'closed', 'won', 'lost'])->default('open');
            $table->integer('assigned_to')->nullable()->comment('user_id');
            $table->integer('created_by')->nullable();
            $table->timestamps(6);
            $table->softDeletes();
            
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('cascade');
            $table->foreign('pipeline_stage_id')->references('id')->on('pipeline_stages')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
