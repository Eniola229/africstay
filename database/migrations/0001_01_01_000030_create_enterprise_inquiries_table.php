<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enterprise_inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('contact_name');
            $table->string('hotel_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['new', 'contacted', 'converted', 'closed'])->default('new');
            $table->uuid('assigned_to')->nullable(); // platform_admin_id
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_inquiries');
    }
};
