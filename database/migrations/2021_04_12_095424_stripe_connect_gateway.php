<?php

use App\Models\Gateway;
use App\Utils\Ninja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StripeConnectGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Model::unguard();

        $gateway = [
            'id' => 56,
            'name' => 'Stripe Connect',
            'provider' => 'StripeConnect',
            'sort_order' => 1,
            'key' => 'd14dd26a47cecc30fdd65700bfb67b34',
            'fields' => '{"apiKey":"", "publishableKey":""}'
        ];

        Gateway::create($gateway);

        if (Ninja::isNinja()) {
            Gateway::where('id', 20)->update(['visible' => 0]);
            Gateway::where('id', 56)->update(['visible' => 1]);
        }

        Model::reguard();
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
