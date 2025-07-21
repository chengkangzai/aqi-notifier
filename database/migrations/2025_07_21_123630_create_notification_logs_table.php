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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient'); // WhatsApp number
            $table->string('city');
            $table->integer('aqi_value');
            $table->string('aqi_level'); // good, moderate, unhealthy, etc.
            $table->text('message_content');
            $table->string('status')->default('sent'); // sent, failed, delivered
            $table->json('response_data')->nullable(); // WAHA API response
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['recipient', 'sent_at']);
            $table->index(['aqi_level', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
