<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2023. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 *
 * Documentation of Api-Usage: https://developer.gocardless.com/bank-account-data/overview
 */

namespace App\Helpers\Bank\Nordigen;

use App\Exceptions\NordigenApiException;
use App\Helpers\Bank\Nordigen\Transformer\AccountTransformer;
use App\Helpers\Bank\Nordigen\Transformer\IncomeTransformer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Generate new access token. Token is valid for 24 hours
// Token is automatically injected into every response
$token = $client->createAccessToken();

// Get access token
$accessToken = $client->getAccessToken();
// Get refresh token
$refreshToken = $client->getRefreshToken();

// Exchange refresh token for new access token
$newToken = $client->refreshAccessToken($refreshToken);

// Get list of institutions by country. Country should be in ISO 3166 standard.
$institutions = $client->institution->getInstitutionsByCountry("LV");

// Institution id can be gathered from getInstitutions response.
// Example Revolut ID
$institutionId = "REVOLUT_REVOGB21";
$redirectUri = "https://nordigen.com";

// Initialize new bank connection session
$session = $client->initSession($institutionId, $redirectUri);

// Get link to authorize in the bank
// Authorize with your bank via this link, to gain access to account data
$link = $session["link"];
// requisition id is needed to get accountId in the next step
$requisitionId = $session["requisition_id"];

class Nordigen
{
    public bool $test_mode = false; // https://developer.gocardless.com/bank-account-data/sandbox

    public string $sandbox_institutionId = "SANDBOXFINANCE_SFIN0000";

    protected \Nordigen\NordigenPHP\API\NordigenClient $client;

    public function __construct(string $client_id, string $client_secret)
    {

        $this->client = new \Nordigen\NordigenPHP\API\NordigenClient($client_id, $client_secret);

    }

    // metadata-section for frontend
    public function getInstitutions()
    {

        if ($this->test_mode)
            return (array) $this->client->institution->getInstitution($this->sandbox_institutionId);

        return $this->client->institution->getInstitutions();
    }

    // requisition-section
    public function createRequisition(string $redirect, string $initutionId)
    {
        if ($this->test_mode && $initutionId != $this->sandbox_institutionId)
            throw new \Exception('invalid institutionId while in test-mode');

        return $this->client->requisition->createRequisition($redirect, $initutionId);
    }

    public function getRequisition(string $requisitionId)
    {
        return $this->client->requisition->getRequisition($requisitionId);
    }

    public function cleanupRequisitions()
    {
        $requisitions = $this->client->requisition->getRequisitions();

        foreach ($requisitions as $requisition) {
            // filter to expired OR older than 7 days created and no accounts
            if ($requisition->status == "EXPIRED" || (sizeOf($requisition->accounts) != 0 && strtotime($requisition->created) > (new \DateTime())->modify('-7 days')))
                continue;

            $this->client->requisition->deleteRequisition($requisition->id);
        }
    }

    // account-section: these methods should be used to get data of connected accounts
    public function getAccounts()
    {

        // get all valid requisitions
        $requisitions = $this->client->requisition->getRequisitions();

        // fetch all valid accounts for activated requisitions
        $nordigen_accountIds = [];
        foreach ($requisitions as $requisition) {
            foreach ($requisition->accounts as $accountId) {
                array_push($nordigen_accountIds, $accountId);
            }
        }

        $nordigen_accountIds = array_unique($nordigen_accountIds);

        $nordigen_accounts = [];
        foreach ($nordigen_accountIds as $accountId) {
            $nordigen_account = $this->getAccount($accountId);

            array_push($nordigen_accounts, $nordigen_account);
        }


        return $nordigen_accounts;

    }

    public function getAccount(string $account_id)
    {

        $out = new \stdClass();

        $out->data = $this->client->account($account_id)->getAccountDetails();
        $out->metadata = $this->client->account($account_id)->getAccountMetaData();
        $out->balances = $this->client->account($account_id)->getAccountBalances();
        $out->institution = $this->client->institution->getInstitution($out->metadata["institution_id"]);

        $it = new AccountTransformer();
        return $it->transform($out);

    }

    public function isAccountActive(string $account_id)
    {

        try {
            $account = $this->client->account($account_id)->getAccountMetaData();

            if ($account["status"] != "READY")
                return false;

            return true;
        } catch (\Exception $e) {
            // TODO: check for not-found exception
            return false;
        }

    }

    /**
     * this method will remove all according requisitions => this can result in removing multiple accounts, if a user reuses a requisition
     */
    public function deleteAccount(string $account_id)
    {

        // get all valid requisitions
        $requisitions = $this->client->requisition->getRequisitions();

        // fetch all valid accounts for activated requisitions
        foreach ($requisitions as $requisition) {
            foreach ($requisition->accounts as $accountId) {

                if ($accountId) {
                    $this->client->requisition->deleteRequisition($accountId);
                }

            }
        }

    }

    public function getTransactions(string $accountId, string $dateFrom = null)
    {

        return $this->client->account($accountId)->getAccountTransactions($dateFrom);

    }
}
