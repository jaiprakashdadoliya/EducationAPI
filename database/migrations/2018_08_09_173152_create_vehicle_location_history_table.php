<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleLocationHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('vehicle_location_history')) {
            Schema::create('vehicle_location_history', function (Blueprint $table) {
                $table->increments('vehicle_location_history_id');
                $table->decimal('vehicle_location_history_latitude', 10, 8)->nullable();
                $table->decimal('vehicle_location_history_longitude', 11, 8)->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
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
        Schema::dropIfExists('vehicle_location_history');
    }
}
