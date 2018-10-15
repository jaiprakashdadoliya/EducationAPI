<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->increments('vehicle_id');
                $table->string('vehicle_reference', 20)->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('vehicle_name', 20)->nullable();
                $table->string('registration_number', 20)->nullable();
                $table->string('chassis_number', 30)->nullable();
                $table->enum('vehicle_type', ['bus','mini-bus','van'])->default('bus');
                $table->string('model', 50)->nullable();
                $table->string('vehicle_photo', 50)->nullable();
                $table->string('registration_document', 50)->nullable();
                $table->string('permit_document', 50)->nullable();
                $table->string('insurance_document', 255)->nullable();
                $table->smallInteger('bus_capacity')->nullable();
                $table->date('bus_permit_validity')->nullable();
                $table->date('bus_insurance')->nullable();
                $table->date('last_maintenance')->nullable();
                $table->string('emergency_contact_number', 20)->nullable();
                $table->smallInteger('bus_sefety_rating')->default('0');
                $table->enum('gps_enabled', ['Yes', 'No'])->default('No');
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('otp', 255)->nullable();
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
        Schema::dropIfExists('vehicles');
    }
}
