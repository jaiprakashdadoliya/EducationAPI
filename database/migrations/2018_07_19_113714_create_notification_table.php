<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->increments('notification_id');
                $table->integer('user_id')->comment('Foreign key for user table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('notification_type', 50)->nullable();
                $table->string('title', 50)->nullable();
                $table->string('description', 255)->nullable();
                $table->string('payload', 30);
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 50)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->integer('approved_by')->nullable();
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
        Schema::dropIfExists('notification');
    }
}
