<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRouteStoppagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('route_stoppages')) {
            Schema::create('route_stoppages', function (Blueprint $table) {
                $table->increments('route_stoppage_id');
                $table->integer('route_id')->comment('Foreign key for route table')->nullable();
                $table->integer('stoppage_id')->comment('Foreign key for stoppage table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->smallInteger('duration')->nullable();
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
        Schema::dropIfExists('route_stoppages');
    }
}
