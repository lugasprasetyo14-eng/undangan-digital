<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->string('reference', 64)->unique();
            $table->string('package_name');
            $table->unsignedInteger('amount');
            $table->string('channel', 40);
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->string('status', 30)->default('pending');
            $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
            $table->string('trx_id')->nullable()->unique();
            $table->text('pay_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['invitation_id', 'package_id', 'active_slot'], 'invoice_active_purchase');
        });
        Schema::create('invitation_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamp('activated_at');
            $table->timestamps();
            $table->unique(['invitation_id', 'package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_entitlements');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('packages');
    }
};