<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSchoolsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('schools')) {
            Schema::create('schools', function (Blueprint $table) {
                $table->increments('school_id');
                $table->string('name', 50)->nullable();
                $table->string('logo', 50)->nullable();
                $table->string('contact_first_name', 50)->nullable();
                $table->string('contact_last_name', 50)->nullable();
                $table->string('address_line_1', 255)->nullable();
                $table->string('address_line_2', 255)->nullable();
                $table->string('contact_phone', 15)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('postcode', 15)->nullable();
                $table->smallInteger('capacity')->nullable();
                $table->string('notification_message', 150)->nullable();
                $table->smallInteger('is_invoice_notification')->nullable();
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
        Schema::dropIfExists('schools');
    }
}
