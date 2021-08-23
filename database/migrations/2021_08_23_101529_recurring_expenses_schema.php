<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecurringExpensesSchema extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recurring_expenses', function ($table) {
            
            $table->increments('id');
            $table->timestamps(6);
            $table->softDeletes();

            $table->unsignedInteger('company_id')->index();
            $table->unsignedInteger('vendor_id')->nullable();
            $table->unsignedInteger('user_id');

            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('client_id')->nullable();
            $table->unsignedInteger('bank_id')->nullable();
            $table->unsignedInteger('payment_type_id')->nullable();
            $table->unsignedInteger('recurring_expense_id')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->string('tax_name1')->nullable();
            $table->string('tax_name2')->nullable();
            $table->string('tax_name3')->nullable();
            $table->date('date')->nullable();
            $table->date('payment_date')->nullable();
            $table->boolean('should_be_invoiced')->default(false);
            $table->boolean('invoice_documents')->default();
            $table->string('transaction_id')->nullable();
            $table->string('custom_value1')->nullable();
            $table->string('custom_value2')->nullable();
            $table->string('custom_value3')->nullable();
            $table->string('custom_value4')->nullable();

            $table->unsignedInteger('category_id')->nullable();
            $table->boolean('calculate_tax_by_amount')->default(false);
            $table->decimal('tax_rate1', 20, 6);
            $table->decimal('tax_rate2', 20, 6);
            $table->decimal('tax_rate3', 20, 6);
            $table->decimal('amount', 20, 6);
            $table->decimal('foreign_amount', 20, 6);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->unsignedInteger('assigned_user_id')->nullable();
            $table->string('number')->nullable();
            $table->unsignedInteger('invoice_currency_id')->nullable();
            $table->unsignedInteger('expense_currency_id')->nullable();
            $table->text('private_notes')->nullable();
            $table->text('public_notes')->nullable();
            $table->text('transaction_reference')->nullable();

            $table->unsignedInteger('frequency_id');
            $table->datetime('start_date')->nullable();
            $table->datetime('last_sent_date')->nullable();
            $table->datetime('next_send_date')->nullable();
            $table->unsignedInteger('remaining_cycles')->nullable();

            $table->unique(['company_id', 'number']);
            $table->index(['company_id', 'deleted_at']);

            // Relations
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
