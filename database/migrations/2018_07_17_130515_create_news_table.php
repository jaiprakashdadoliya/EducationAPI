<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->increments('news_id');
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->string('title', 50)->nullable();
                $table->string('image', 50)->nullable();
                $table->longText('description')->nullable();
                $table->dateTime('news_date')->nullable();
                $table->smallInteger('status')->default(1);
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
        Schema::dropIfExists('news');
    }
}
