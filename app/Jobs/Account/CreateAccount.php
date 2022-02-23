<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2021. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Jobs\Account;

use App\DataMapper\Analytics\AccountCreated as AnalyticsAccountCreated;
use App\DataMapper\Analytics\AccountPlatform;
use App\Events\Account\AccountCreated;
use App\Jobs\Company\CreateCompany;
use App\Jobs\Company\CreateCompanyPaymentTerms;
use App\Jobs\Company\CreateCompanyTaskStatuses;
use App\Jobs\Company\CreateCompanyToken;
use App\Jobs\Mail\NinjaMailer;
use App\Jobs\Mail\NinjaMailerJob;
use App\Jobs\Mail\NinjaMailerObject;
use App\Jobs\User\CreateUser;
use App\Jobs\Util\VersionCheck;
use App\Mail\Admin\AccountCreatedObject;
use App\Mail\Admin\VerifyUserObject;
use App\Models\Account;
use App\Models\Timezone;
use App\Notifications\Ninja\NewAccountCreated;
use App\Utils\Ninja;
use App\Utils\Traits\User\LoginCache;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Turbo124\Beacon\Facades\LightLogs;
use Illuminate\Support\Facades\App;

class CreateAccount
{
    use Dispatchable;
    use LoginCache;

    protected $request;

    protected $client_ip;

    public function __construct(array $sp660339, $client_ip)
    {
        $this->request = $sp660339;
        $this->client_ip = $client_ip;
    }

    public function handle()
    {
        if (config('ninja.environment') == 'selfhost' && Account::all()->count() == 0) {
            return $this->create();
        } elseif (config('ninja.environment') == 'selfhost' && Account::all()->count() > 1) {
            return response()->json(['message' => Ninja::selfHostedMessage()], 400);
        } elseif (! Ninja::boot()) {
            return response()->json(['message' => Ninja::parse()], 401);
        }

        return $this->create();
    }

    private function create()
    {
        Account::reguard();
        $sp794f3f = new Account();
        $sp794f3f->fill($this->request);

        if(array_key_exists('rc', $this->request))
            $sp794f3f->referral_code = $this->request['rc'];

        if (! $sp794f3f->key) {
            $sp794f3f->key = Str::random(32);
        }

        if(Ninja::isHosted())
        {
            $sp794f3f->hosted_client_count = config('ninja.quotas.free.clients');
            $sp794f3f->hosted_company_count = config('ninja.quotas.free.max_companies');
            // $sp794f3f->trial_started = now();
            // $sp794f3f->trial_plan = 'pro';
        
        }
        
        $sp794f3f->save();

        $sp035a66 = CreateCompany::dispatchNow($this->request, $sp794f3f);
        $sp035a66->load('account');
        $sp794f3f->default_company_id = $sp035a66->id;
        $sp794f3f->save();

        $spaa9f78 = CreateUser::dispatchNow($this->request, $sp794f3f, $sp035a66, true);

        CreateCompanyPaymentTerms::dispatchNow($sp035a66, $spaa9f78);
        CreateCompanyTaskStatuses::dispatchNow($sp035a66, $spaa9f78);

        if ($spaa9f78) {
            auth()->login($spaa9f78, false);
        }

        $spaa9f78->setCompany($sp035a66);
        $this->setLoginCache($spaa9f78);

        $spafe62e = isset($this->request['token_name']) ? $this->request['token_name'] : request()->server('HTTP_USER_AGENT');
        $sp2d97e8 = CreateCompanyToken::dispatchNow($sp035a66, $spaa9f78, $spafe62e);

        if ($spaa9f78) {
            event(new AccountCreated($spaa9f78, $sp035a66, Ninja::eventVars()));
        }

        $spaa9f78->fresh();

        if(Ninja::isHosted()){

            App::forgetInstance('translator');
            $t = app('translator');
            $t->replace(Ninja::transformTranslations($sp035a66->settings));

            $nmo = new NinjaMailerObject;
            $nmo->mailable = new \Modules\Admin\Mail\Welcome($sp035a66->owner());
            $nmo->company =  $sp035a66;
            $nmo->settings = $sp035a66->settings;
            $nmo->to_user = $sp035a66->owner();

            NinjaMailerJob::dispatch($nmo);

            \Modules\Admin\Jobs\Account\NinjaUser::dispatch([], $sp035a66);
        }

        VersionCheck::dispatch();

        LightLogs::create(new AnalyticsAccountCreated())
                 ->increment()
                 ->queue();
                 
        $ip = '';
        
        if(request()->hasHeader('Cf-Connecting-Ip'))
            $ip = request()->header('Cf-Connecting-Ip');
        elseif(request()->hasHeader('X-Forwarded-For'))
            $ip = request()->header('Cf-Connecting-Ip');
        else
            $ip = request()->ip();

        $platform = request()->has('platform') ? request()->input('platform') : 'www';

        LightLogs::create(new AccountPlatform($platform, request()->server('HTTP_USER_AGENT'), $ip))
                 ->queue();        

        return $sp794f3f;
    }

    // private function processSettings($settings)
    // {
    //     if(Ninja::isHosted() && Cache::get('currencies'))
    //     {

    //         $currency = Cache::get('currencies')->filter(function ($item) use ($currency_code) {
    //             return strtolower($item->code) == $currency_code;
    //         })->first();

    //         if ($currency) {
    //             $settings->currency_id = (string)$currency->id;
    //         }

    //         $country = Cache::get('countries')->filter(function ($item) use ($country_code) {
    //             return strtolower($item->iso_3166_2) == $country_code || strtolower($item->iso_3166_3) == $country_code;
    //         })->first();

    //         if ($country) {
    //             $settings->country_id = (string)$country->id;
    //         }
            
    //         $language = Cache::get('languages')->filter(function ($item) use ($currency_code) {
    //             return strtolower($item->locale) == $currency_code;
    //         })->first();

    //         if ($language) {
    //             $settings->language_id = (string)$language->id;
    //         }

    //         if($timezone) {
    //             $settings->timezone_id = (string)$timezone->id;
    //         }

    //         return $settings;
    //     }


    //     return $settings;
    // }
}




