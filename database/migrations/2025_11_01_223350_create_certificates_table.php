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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('unique_id')->unique()->index();
            $table->string('recipient_name');
            $table->string('recipient_email')->nullable();
            $table->enum('state', ['attending', 'completing']);
            $table->enum('event_type', ['workshop', 'course']);
            $table->string('event_title');
            $table->date('issue_date');
            $table->string('issuer_name');
            $table->string('org_name');
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
