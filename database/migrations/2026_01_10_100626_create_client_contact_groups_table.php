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
        Schema::create('client_contact_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->integer('client_id');
            $table->integer('contact_group_id');
            $table->timestamps(6);
            
            $table->primary(['client_id', 'contact_group_id']);
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->foreign('contact_group_id')->references('id')->on('contact_groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contact_groups');
    }
};
