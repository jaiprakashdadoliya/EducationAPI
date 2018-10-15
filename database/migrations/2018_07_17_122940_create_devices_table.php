<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->increments('device_id');
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->integer('user_id')->comment('Foreign key for user table')->nullable();
                $table->string('device_reference', 50)->nullable();
                $table->string('device_name', 20)->nullable();
                $table->string('device_token', 255)->nullable();
                $table->string('os_version', 20)->nullable();
                $table->string('device_model', 20)->nullable();
                $table->enum('device_type', ['ios', 'android'])->default('android')->nullable();
                $table->enum('user_type', ['driver', 'assistant', 'parent'])->default('assistant')->nullable();
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->enum('user_type', ['assistant', 'parent'])->default('parent')->nullable();
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
        Schema::dropIfExists('devices');
    }
}
