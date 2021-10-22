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

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientPortal\Contact\ContactPasswordResetRequest;
use App\Libraries\MultiDB;
use App\Models\Account;
use App\Models\ClientContact;
use App\Models\Company;
use App\Utils\Ninja;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:contact');
    }

    /**
     * Show the reset email form.
     *
     * @return Factory|View
     */
    public function showLinkRequestForm(Request $request)
    {
        $account_id = $request->get('account_id');
        $account = Account::find($account_id);
        $company = $account->companies->first();

        return $this->render('auth.passwords.request', [
            'title' => 'Client Password Reset',
            'passwordEmailRoute' => 'client.password.email',
            'account' => $account,
            'company' => $company
        ]);
    }

    protected function guard()
    {
        return Auth::guard('contact');
    }

    public function broker()
    {
        return Password::broker('contacts');
    }

    public function sendResetLinkEmail(ContactPasswordResetRequest $request)
    {
        
        if(Ninja::isHosted() && $request->has('company_key'))
            MultiDB::findAndSetDbByCompanyKey($request->input('company_key'));
        
        $this->validateEmail($request);

        // $user = MultiDB::hasContact($request->input('email'));
        $company = Company::where('company_key', $request->input('company_key'))->first();
        $contact = MultiDB::findContact(['company_id' => $company->id, 'email' => $request->input('email')]);

        $response = false;

        if($contact){

            /* Update all instances of the client */
            $token = Str::random(60);
            ClientContact::where('email', $contact->email)->update(['token' => $token]);
            
            $contact->sendPasswordResetNotification($token);
            $response = Password::RESET_LINK_SENT;
        }
        else
            return $this->sendResetLinkFailedResponse($request, Password::INVALID_USER);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        // $response = $this->broker()->sendResetLink(
        //     $this->credentials($request)
        // );

        if ($request->ajax()) {

            if($response == Password::RESET_THROTTLED)
                return response()->json(['message' => ctrans('passwords.throttled'), 'status' => false], 429);

            return $response == Password::RESET_LINK_SENT
                ? response()->json(['message' => 'Reset link sent to your email.', 'status' => true], 201)
                : response()->json(['message' => 'Email not found', 'status' => false], 401);
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

}
