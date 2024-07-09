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
        Schema::create('email_to_webs', function (Blueprint $table) {
            $table->id();
            $table->string('from_email');
            $table->string('to_email');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('pdf_attachment')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_to_webs');
    }
};
