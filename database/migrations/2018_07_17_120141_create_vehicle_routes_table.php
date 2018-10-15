<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('vehicle_routes')) {
            Schema::create('vehicle_routes', function (Blueprint $table) {
                $table->increments('vehicle_route_id');
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->time('start_time');
                $table->time('end_time');
                $table->enum('shift', ['morning', 'evening'])->default('morning');
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
        Schema::dropIfExists('vehicle_routes');
    }
}
