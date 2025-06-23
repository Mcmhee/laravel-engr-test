<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('email');
            $table->jsonb('specialty_preferences')->nullable();
            $table->enum('date_preference', ['encounter', 'submission'])->default('submission');
            $table->unsignedInteger('min_batch_size')->default(1);
            $table->unsignedInteger('max_batch_size')->default(10);
            $table->unsignedInteger('daily_capacity')->default(100);
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('insurers');
    }
}; 