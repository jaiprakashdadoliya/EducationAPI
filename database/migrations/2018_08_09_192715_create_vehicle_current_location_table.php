<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleCurrentLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_current_location', function (Blueprint $table) {
            $table->increments('vehicle_current_location_id');
            $table->decimal('vehicle_current_location_latitude', 10, 8);
            $table->decimal('vehicle_current_location_longitude', 11, 8);
            $table->integer('vehicle_id');
            $table->integer('route_id');
            $table->integer('vehicle_route_id');
            $table->integer('school_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->enum('resource_type', ['web', 'ios', 'android'])->default('web');
            $table->softDeletes();
            $table->string('user_agent')->nullable();
            $table->string('ip_address', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vehicle_current_location');
    }
}
