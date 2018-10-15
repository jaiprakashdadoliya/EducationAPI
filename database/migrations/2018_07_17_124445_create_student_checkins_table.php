<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentCheckinsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('student_checkins')) {
            Schema::create('student_checkins', function (Blueprint $table) {
                $table->increments('student_checkin_id');
                $table->integer('student_id')->comment('Foreign key for student table')->nullable();
                $table->integer('user_id')->comment('Foreign key for user table')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->integer('checkin_stoppage_id')->nullable();
                $table->integer('checkout_stoppage_id')->nullable();
                $table->decimal('checkin_latitude', 10, 8)->nullable();
                $table->decimal('checkin_longitude', 11, 8)->nullable();
                $table->decimal('checkout_latitude', 10, 8)->nullable();
                $table->decimal('checkout_longitude', 11, 8)->nullable();
                $table->enum('checkin_source', ['assistant', 'parent'])->default('assistant');
                $table->enum('checkout_source', ['assistant', 'parent'])->default('assistant');
                $table->enum('route_type', ['drop', 'pickup'])->default('pickup');
                $table->dateTime('checkin_time')->nullable();
                $table->dateTime('checkout_time')->nullable();
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->integer('checkin_approved_by')->nullable();
                $table->integer('checkout_approved_by')->nullable();
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
        Schema::dropIfExists('student_checkins');
    }
}
