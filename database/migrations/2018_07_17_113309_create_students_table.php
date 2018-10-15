<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->increments('student_id');
                $table->string('student_reference', 20)->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('name', 50)->nullable();
                $table->string('class', 10)->nullable();
                $table->integer('beacon_id')->nullable();
                $table->string('beacon_name', 10)->nullable();
                $table->string('student_picture', 100)->nullable();
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
        Schema::dropIfExists('students');
    }
}
