<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentVehicleRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('student_vehicle_routes')) {
            Schema::create('student_vehicle_routes', function (Blueprint $table) {
                $table->increments('student_vehicle_route_id');
                $table->string('student_vehicle_route_reference',20)->nullable();
                $table->integer('student_id')->comment('Foreign key for student table')->nullable();
                $table->integer('vehicle_route_id')->comment('Foreign key for vehicle route table')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->time('start_time');
                $table->time('pickup_time');
                $table->integer('stoppage_pickup')->comment('Foreign key for stoppage table')->nullable();
                $table->time('drop_time');
                $table->integer('stoppage_drop')->comment('Foreign key for stoppage table')->nullable();
                $table->enum('route_type', ['pickup', 'drop'])->default('pickup');
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
        Schema::dropIfExists('student_vehicle_routes');
    }
}
