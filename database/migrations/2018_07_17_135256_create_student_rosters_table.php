<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentRostersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('student_rosters')) {
            Schema::create('student_rosters', function (Blueprint $table) {
                $table->increments('student_roster_id');
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->integer('student_id')->comment('Foreign key for student table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->enum('vehicle_shift', ['morning',  'evening'])->default('morning');
                $table->date('roster_date');
                $table->time('start_time');
                $table->time('end_time');
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
        Schema::dropIfExists('student_rosters');
    }
}
