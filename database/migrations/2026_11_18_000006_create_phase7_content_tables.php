<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitation_messages', function (Blueprint $table) {
            $table->id(); $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('invitation_guests')->nullOnDelete();
            $table->string('name', 120); $table->text('message'); $table->string('status', 20)->default('pending')->index(); $table->timestamps();
            $table->index(['invitation_id', 'status']);
        });
        Schema::create('invitation_media', function (Blueprint $table) {
            $table->id(); $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); $table->string('path')->nullable(); $table->string('thumbnail_path')->nullable(); $table->string('external_url')->nullable();
            $table->string('caption')->nullable(); $table->unsignedInteger('sort_order')->default(0); $table->timestamps();
            $table->index(['invitation_id', 'type', 'sort_order']);
        });
        Schema::create('invitation_gift_options', function (Blueprint $table) {
            $table->id(); $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); $table->string('label', 120); $table->string('account_value', 120)->nullable(); $table->string('account_name', 120)->nullable(); $table->string('qr_path')->nullable(); $table->boolean('enabled')->default(true); $table->timestamps();
            $table->index(['invitation_id', 'enabled']);
        });
        Schema::create('invitation_wishlists', function (Blueprint $table) {
            $table->id(); $table->foreignId('invitation_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160); $table->text('note')->nullable(); $table->string('url')->nullable(); $table->boolean('enabled')->default(true); $table->timestamps();
            $table->index(['invitation_id', 'enabled']);
        });
        Schema::create('wishlist_reservations', function (Blueprint $table) {
            $table->id(); $table->foreignId('wishlist_id')->constrained('invitation_wishlists')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('invitation_guests')->cascadeOnDelete();
            $table->timestamp('reserved_at'); $table->timestamp('cancelled_at')->nullable(); $table->unsignedTinyInteger('active_slot')->nullable(); $table->timestamps();
            $table->unique(['wishlist_id', 'active_slot']);
            $table->index(['wishlist_id', 'cancelled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_reservations'); Schema::dropIfExists('invitation_wishlists'); Schema::dropIfExists('invitation_gift_options'); Schema::dropIfExists('invitation_media'); Schema::dropIfExists('invitation_messages');
    }
};