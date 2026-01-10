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
        // Drop table if it exists (from previous failed migration attempt)
        Schema::dropIfExists('pipeline_stages');
        
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('id', true);
            $table->integer('pipeline_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#6c5ce7');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_default_stage')->default(false);
            $table->timestamps(6);
            $table->softDeletes();
            
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
