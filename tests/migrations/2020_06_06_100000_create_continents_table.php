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
        Schema::create('continents', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name', 50);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('timezone_id', 32)->nullable()->index();
            $table->bigInteger('population')->unsigned()->nullable();
            $table->smallInteger('dem')->nullable()->comment('Digital elevation model, srtm3 or gtopo30.');
            $table->string('feature_code', 10)->nullable()->comment('See: https://www.geonames.org/export/codes.html.');
            $table->integer('geoname_id')->unsigned()->unique()->comment('Geonames database identifier.');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('continents');
    }
};