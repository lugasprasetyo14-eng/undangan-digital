<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('category', 30)->nullable()->index();
            $table->unsignedSmallInteger('max_guests')->default(1);
            $table->string('rsvp_token', 64)->unique();
            $table->string('qr_token', 64)->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('rsvp_status', 20)->default('pending')->index();
            $table->unsignedSmallInteger('attending_count')->default(0);
            $table->timestamp('rsvp_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['invitation_id', 'slug']);
            $table->index(['invitation_id', 'rsvp_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_guests');
    }
};