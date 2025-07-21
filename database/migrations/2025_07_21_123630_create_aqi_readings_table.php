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
        Schema::create('aqi_readings', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->integer('aqi_value');
            $table->string('dominant_pollutant')->nullable();
            
            // Individual pollutant readings
            $table->decimal('pm25', 8, 2)->nullable();
            $table->decimal('pm10', 8, 2)->nullable();
            $table->decimal('o3', 8, 2)->nullable();
            $table->decimal('no2', 8, 2)->nullable();
            $table->decimal('so2', 8, 2)->nullable();
            $table->decimal('co', 8, 2)->nullable();
            
            // Weather data
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('humidity')->nullable();
            $table->integer('pressure')->nullable();
            $table->decimal('wind_speed', 5, 2)->nullable();
            
            // Location and timing
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('reading_time'); // From API timestamp
            $table->json('raw_response'); // Full API response for reference
            
            $table->timestamps();

            $table->index(['city', 'reading_time']);
            $table->index(['aqi_value', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aqi_readings');
    }
};
