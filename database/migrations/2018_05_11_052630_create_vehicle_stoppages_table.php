<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleStoppagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('vehicle_stoppages')) {
            Schema::create('vehicle_stoppages', function (Blueprint $table) {
                $table->increments('vehicle_stoppage_id');
                $table->decimal('vehicle_stoppage_latitude', 10, 8);
                $table->decimal('vehicle_stoppage_longitude', 11, 8);
                $table->integer('school_id');
                $table->integer('vehicle_id');
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->timestamps();
                $table->enum('resource_type', ['web', 'ios', 'android'])->default('web');
                $table->smallInteger('is_deleted')->default(0);
                $table->string('user_agent')->nullable();
                $table->string('ip_address', 50)->nullable();
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
        Schema::dropIfExists('vehicle_stoppages');
    }
}