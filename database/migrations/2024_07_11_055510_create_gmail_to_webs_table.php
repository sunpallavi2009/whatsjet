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
        Schema::create('gmail_to_webs', function (Blueprint $table) {
            $table->id();
            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->text('attachments')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->unsignedBigInteger('vendors__id')->nullable();
            $table->foreign('vendors__id')->references('_id')->on('vendors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gmail_to_webs');
    }
};
