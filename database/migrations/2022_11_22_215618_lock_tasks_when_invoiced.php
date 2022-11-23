<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tasks', function (Blueprint $table)
        {
            $table->boolean('invoice_lock')->default(false);
        });

        Schema::table('companies', function (Blueprint $table)
        {
            $table->boolean('invoice_task_lock')->default(false);
        });

        Schema::table('bank_transactions', function (Blueprint $table)
        {
            $table->bigInteger('bank_rule_id')->nullable();
        });

        Schema::table('subscriptions', function (Blueprint $table)
        {
            $table->boolean('registration_required')->default(false);
            $table->text('optional_product_ids')->nullable();
            $table->text('optional_recurring_product_ids')->nullable();
            
        });

        $currencies = [

            ['id' => 113, 'name' => 'Swazi lilangeni', 'code' => 'SZL', 'symbol' => 'E', 'precision' => '2', 'thousand_separator' => ',', 'decimal_separator' => '.'],

        ];

        foreach ($currencies as $currency) {
            $record = Currency::whereCode($currency['code'])->first();
            if ($record) {
                $record->name = $currency['name'];
                $record->symbol = $currency['symbol'];
                $record->precision = $currency['precision'];
                $record->thousand_separator = $currency['thousand_separator'];
                $record->decimal_separator = $currency['decimal_separator'];
                if (isset($currency['swap_currency_symbol'])) {
                    $record->swap_currency_symbol = $currency['swap_currency_symbol'];
                }
                $record->save();
            } else {
                Currency::create($currency);
            }
        }

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
};
