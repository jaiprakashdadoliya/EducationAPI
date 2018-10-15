<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         if (!Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->increments('trip_id');
                $table->integer('current_stoppage_id')->comment('Foreign key for stoppages table')->nullable();
                $table->smallInteger('delay')->default('0');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->integer('vehicle_route_id')->comment('Foreign key for routes table')->nullable();
                $table->integer('assistant_id')->comment('Foreign key for users table')->nullable();
                $table->integer('driver_id')->comment('Foreign key for users table')->nullable();
                $table->integer('school_id')->comment('Foreign key for schools table')->nullable();
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->comment('Soft Delete. Default value 0')->default(0);
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
        Schema::dropIfExists('trips');
    }
}
