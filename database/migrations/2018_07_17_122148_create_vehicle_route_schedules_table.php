<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleRouteSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('vehicle_route_schedules')) {
            Schema::create('vehicle_route_schedules', function (Blueprint $table) {
                $table->increments('vehicle_route_schedule_id');
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
                $table->integer('stoppage_id')->comment('Foreign key for stoppage table')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->integer('vehicle_route_id')->comment('Foreign key for vehicle route table')->nullable();
                $table->time('schedule_time');
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
        Schema::dropIfExists('vehicle_route_schedules');
    }
}
