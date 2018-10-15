<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->increments('invoice_id');
                $table->string('invoice_number', 10);
                $table->integer('user_id')->comment('Foreign key for user table')->nullable();
                $table->integer('account_id')->comment('Foreign key for account table')->nullable();
                $table->integer('student_id')->comment('Foreign key for student table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->date('invoice_date');
                $table->date('due_date');
                $table->decimal('amount', 10, 2);
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
        Schema::dropIfExists('invoices');
    }
}
