<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripStoppagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
         if (!Schema::hasTable('trip_stoppages')) {
            Schema::create('trip_stoppages', function (Blueprint $table) {
                $table->increments('trip_stoppage_id');
                $table->integer('trip_id')->nullable();
                $table->integer('stoppage_id')->nullable();
                $table->time('reaching_time')->nullable();
                $table->integer('school_id')->comment('Foreign key for schools table')->nullable();
                $table->integer('created_by')->nullable();
                $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('trip_stoppages');
    }
}
