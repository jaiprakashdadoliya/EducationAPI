<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBeaconsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('beacons')) {
            Schema::create('beacons', function (Blueprint $table) {
                $table->increments('beacon_id');
                $table->string('major', 255)->nullable();
                $table->string('miner', 255)->nullable();
                $table->string('uuid', 255)->nullable();
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
        Schema::dropIfExists('beacons');
    }
}
