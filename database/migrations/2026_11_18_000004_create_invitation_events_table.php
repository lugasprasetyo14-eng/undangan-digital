<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30)->default('other')->index();
            // Stored as local wall-clock datetime; timezone identifies its IANA zone.
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->string('timezone', 64)->default('Asia/Jakarta');
            $table->string('venue_name')->nullable();
            $table->text('address')->nullable();
            $table->string('maps_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_events');
    }
};