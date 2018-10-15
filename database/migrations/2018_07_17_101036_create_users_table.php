<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('user_id');
                $table->string('name', 255);
                $table->string('user_reference', 20)->nullable();
                $table->string('password', 255)->nullable();
                $table->string('email', 255)->unique();
                $table->string('mobile', 15)->unique();
                $table->enum('driver_police_verification',['Yes','No'] )->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('driver_rating')->length(5)->default('0');
                $table->string('address', 255)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('postcode', 15)->nullable();
                $table->string('aadhaar_number', 12)->nullable();
                $table->string('driving_licence_number', 30)->nullable();
                $table->string('picture', 100)->nullable();
                $table->enum('user_type', ['driver', 'assistant', 'parent', 'admin', 'schooladmin'])->default('assistant');
                $table->smallInteger('notification_time')->default(15);
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->string('short_token', 50)->nullable();
                $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}