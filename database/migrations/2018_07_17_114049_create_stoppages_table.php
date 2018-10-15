<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoppagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('stoppages')) {
            Schema::create('stoppages', function (Blueprint $table) {
                $table->increments('stoppage_id');
                $table->string('stoppage_reference', 20)->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('stoppage_name', 20)->nullable();
                $table->string('stoppage_address', 100)->nullable();
                $table->decimal('stoppage_latitude', 10, 8)->nullable();
                $table->decimal('stoppage_longitude', 11, 8)->nullable();
                $table->enum('location_type', ['school', 'student'])->default('school');
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
        Schema::dropIfExists('stoppages');
    }
}
