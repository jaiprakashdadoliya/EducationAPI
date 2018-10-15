<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckInReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('check_in_reasons')) {
            Schema::create('check_in_reasons', function (Blueprint $table) {
                $table->increments('check_in_reason_id');
                $table->string('reason', 255)->nullable();
                $table->enum('reason_type', ['checkin', 'checkout'])->default('web');
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
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
        Schema::dropIfExists('check_in_reasons');
    }
}
