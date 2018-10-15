<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeviceVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('device_vehicles')) {
            Schema::create('device_vehicles', function (Blueprint $table) {
                $table->increments('device_vehicle_id');
                $table->integer('device_id')->comment('Foreign key for device table')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('device_vehicles');
    }
}
