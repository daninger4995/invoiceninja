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

namespace App\Export\CSV;

use App\Libraries\MultiDB;
use App\Models\Client;
use App\Models\Company;
use App\Models\Credit;
use App\Models\Document;
use App\Transformers\DocumentTransformer;
use App\Utils\Ninja;
use Illuminate\Support\Facades\App;
use League\Csv\Writer;

class DocumentExport extends BaseExport
{
    private Company $company;

    protected array $input;

    private $entity_transformer;

    protected $date_key = 'created_at';

    protected array $entity_keys = [
        'record_type' => 'record_type',
        'record_name' => 'record_name',
        'name' => 'name',
        'type' => 'type',
        'created_at' => 'created_at',
    ];

    protected array $all_keys = [
        'record_type',
        'record_name',
        'name',
        'type',
        'created_at',
    ];

    private array $decorate_keys = [

    ];

    public function __construct(Company $company, array $input)
    {
        $this->company = $company;
        $this->input = $input;
        $this->entity_transformer = new DocumentTransformer();
    }

    public function run()
    {

        MultiDB::setDb($this->company->db);
        App::forgetInstance('translator');
        App::setLocale($this->company->locale());
        $t = app('translator');
        $t->replace(Ninja::transformTranslations($this->company->settings));

        //load the CSV document from a string
        $this->csv = Writer::createFromString();

        if(count($this->input['report_keys']) == 0)
            $this->input['report_keys'] = $this->all_keys;

        //insert the header
        $this->csv->insertOne($this->buildHeader());

        $query = Document::query()->where('company_id', $this->company->id);

        $query = $this->addDateRange($query);

        $query->cursor()
              ->each(function ($entity){

            $this->csv->insertOne($this->buildRow($entity)); 

        });

        return $this->csv->toString(); 

    }

    private function buildRow(Document $document) :array
    {

        $transformed_entity = $this->entity_transformer->transform($document);

        $entity = [];

        foreach(array_values($this->input['report_keys']) as $key){

            $entity[$key] = $transformed_entity[$key];
        
        }

        return $this->decorateAdvancedFields($document, $entity);

    }

    private function decorateAdvancedFields(Document $document, array $entity) :array
    {

        if(array_key_exists('record_type', $entity))
            $entity['record_type'] = class_basename($document->documentable);

        if(array_key_exists('record_name', $entity))
            $entity['record_name'] = $document->hashed_id;

        return $entity;
    }

}
