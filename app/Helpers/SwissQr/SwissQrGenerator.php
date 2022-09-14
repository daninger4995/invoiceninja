<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Helpers\SwissQr;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use Sprain\SwissQrBill as QrBill;

/**
 * SwissQrGenerator.
 */
class SwissQrGenerator 
{

    protected Company $company;

    protected $invoice;

    protected Client $client;

    public function __construct($invoice, Company $company)
    {
        $this->company = $company;

        $this->invoice = $invoice;
    
        $this->client = $invoice->client;
    }

    private function calcDueAmount()
    {
        if($this->invoice->partial > 0)
            return $this->invoice->partial;

        if($this->invoice->status_id == Invoice::STATUS_DRAFT)
            return $this->invoice->amount;

        return $this->invoice->balance;
    }

    public function run()
    {

    // This is an example how to create a typical qr bill:
    // - with reference number
    // - with known debtor
    // - with specified amount
    // - with human-readable additional information
    // - using your QR-IBAN
    //
    // Likely the most common use-case in the business world.

    // Create a new instance of QrBill, containing default headers with fixed values
    $qrBill = QrBill\QrBill::create();


    // Add creditor information
    // Who will receive the payment and to which bank account?
    $qrBill->setCreditor(
        QrBill\DataGroup\Element\CombinedAddress::create(
            $this->company->present()->name(),
            $this->company->present()->address1(),
            $this->company->present()->getCompanyCityState(),
            'CH'
        ));

    $qrBill->setCreditorInformation(
        QrBill\DataGroup\Element\CreditorInformation::create(
            $this->company->present()->qr_iban() ?: '' // This is a special QR-IBAN. Classic IBANs will not be valid here.
        ));

    // Add debtor information
    // Who has to pay the invoice? This part is optional.
    //
    // Notice how you can use two different styles of addresses: CombinedAddress or StructuredAddress
    // They are interchangeable for creditor as well as debtor.
    $qrBill->setUltimateDebtor(
        QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
            substr($this->client->present()->name(), 0 , 70),
            $this->client->address1 ? substr($this->client->address1, 0 , 70) : '',
            $this->client->address2 ? substr($this->client->address2, 0 , 16) : '',
            $this->client->postal_code ? substr($this->client->postal_code, 0, 16) : '',
            $this->client->city ? substr($this->client->postal_code, 0, 35) : '',
            'CH'
        ));

    // Add payment amount information
    // What amount is to be paid?
    $qrBill->setPaymentAmountInformation(
        QrBill\DataGroup\Element\PaymentAmountInformation::create(
            'CHF',
            $this->calcDueAmount()
        ));

    // Add payment reference
    // This is what you will need to identify incoming payments.

    if(strlen($this->company->present()->besr_id()) > 1)
    {
        nlog("i have a besr");

        $referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
            $this->company->present()->besr_id() ?: '',  // You receive this number from your bank (BESR-ID). Unless your bank is PostFinance, in that case use NULL.
            $this->invoice->number// A number to match the payment with your internal data, e.g. an invoice number
        );

        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
                $referenceNumber
            ));

    }
    else{

        nlog("i have no besr");
        $qrBill->setPaymentReference(
            QrBill\DataGroup\Element\PaymentReference::create(
                QrBill\DataGroup\Element\PaymentReference::TYPE_NON
            ));

    }

    // Optionally, add some human-readable information about what the bill is for.
    $qrBill->setAdditionalInformation(
        QrBill\DataGroup\Element\AdditionalInformation::create(
            $this->invoice->public_notes ?: ''
        )
    );


    // Now get the QR code image and save it as a file.
        try {

            $output = new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'en');

            $html = $output
                ->setPrintable(false)
                ->getPaymentPart();

                return $html;

        } catch (\Exception $e) {

            foreach($qrBill->getViolations() as $key => $violation) {
                nlog("qr");
                nlog($violation);
            }

            nlog($e->getMessage());
            
            return '';
            // return $e->getMessage();
        }


    }

}