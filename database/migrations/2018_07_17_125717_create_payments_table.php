<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->increments('payment_id');
                $table->integer('invoice_number')->comment('parent id')->nullable();
                $table->integer('account_id')->comment('Foreign key for account table')->nullable();
                $table->integer('student_id')->comment('Foreign key for student table')->nullable();
                $table->integer('school_id')->comment('Foreign key for school table')->nullable();
                $table->integer('parent_id')->comment('Foreign key for user table')->nullable();;
                $table->string('transaction_id', 50);
                $table->string('account_name', 50);
                $table->date('transaction_date');
                $table->date('settlement_date');
                $table->enum('settlement_status', ['failed', 'pending', 'success'])->default('pending');
                $table->decimal('amount', 10, 2);
                $table->enum('card_type', ['visa', 'master'])->default('visa');
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
        Schema::dropIfExists('payments');
    }
}
