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
        Schema::table('email_to_webs', function (Blueprint $table) {
            $table->unsignedBigInteger('vendors__id')->nullable();
            $table->foreign('vendors__id')->references('_id')->on('vendors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_to_webs', function (Blueprint $table) {
            $table->dropColumn('vendors__id');
        });
    }
};
