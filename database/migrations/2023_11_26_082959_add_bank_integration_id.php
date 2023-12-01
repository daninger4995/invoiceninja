<?php

use App\Models\Account;
use App\Models\BankIntegration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_integrations', function (Blueprint $table) {
            $table->string('integration_type')->default(BankIntegration::INTEGRATION_TYPE_NONE);
        });

        // migrate old account to be used with yodlee
        BankIntegration::query()->where('integration_type', BankIntegration::INTEGRATION_TYPE_NONE)->whereNotNull('account_id')->cursor()->each(function ($bank_integration) {
            $bank_integration->integration_type = BankIntegration::INTEGRATION_TYPE_YODLEE;
            $bank_integration->save();
        });

        // MAYBE migration of account->bank_account_id etc
        Schema::table('accounts', function (Blueprint $table) {
            $table->renameColumn('bank_integration_account_id', 'bank_integration_yodlee_account_id');
            $table->string('bank_integration_nordigen_secret_id')->nullable();
            $table->string('bank_integration_nordigen_secret_key')->nullable();
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
};
