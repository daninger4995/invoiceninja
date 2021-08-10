<?php

use App\Models\Language;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreLanguages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Language::unguard();

        $language = Language::find(29);

        if(!$language){

            Language::create(['id' => 29, 'name' => 'Arabic', 'locale' => 'ar']);

        }

        $language = Language::find(30);

        if(!$language){

            Language::create(['id' => 30, 'name' => 'Persian', 'locale' => 'fa']);

        }

        $language = Language::find(31);

        if(!$language){

            Language::create(['id' => 31, 'name' => 'Latvian', 'locale' => 'lv_LV']);

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
}
