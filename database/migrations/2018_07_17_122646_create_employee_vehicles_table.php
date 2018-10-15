<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('employee_vehicles')) {
            Schema::create('employee_vehicles', function (Blueprint $table) {
                $table->increments('employee_vehicle_id');
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('user_driver_id')->comment('Foreign key for user table')->nullable();
                $table->integer('user_assistant_id')->comment('Foreign key for user table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->date('effective_date');
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
        Schema::dropIfExists('employee_vehicles');
    }
}
