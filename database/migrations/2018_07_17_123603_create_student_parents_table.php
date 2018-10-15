<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStudentParentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_parents', function (Blueprint $table) {
            $table->increments('student_parent_id');
            $table->string('student_parent_reference',20)->nullable();
            $table->integer('school_id')->comment('Foreign key for school table')->nullable();
            $table->integer('user_id')->comment('Foreign key for user table')->nullable();
            $table->integer('student_id')->comment('Foreign key for student table')->nullable();
            $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->smallInteger('is_deleted')->default(0);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('student_parents');
    }
}
