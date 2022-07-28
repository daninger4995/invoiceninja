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

namespace App\Http\Controllers;

use App\Http\Requests\Import\ImportRequest;
use App\Http\Requests\Import\PreImportRequest;
use App\Jobs\Import\CSVImport;
use App\Jobs\Import\CSVIngest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class ImportController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param PreImportRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *      path="/api/v1/preimport",
     *      operationId="preimport",
     *      tags={"imports"},
     *      summary="Pre Import checks - returns a reference to the job and the headers of the CSV",
     *      description="Pre Import checks - returns a reference to the job and the headers of the CSV",
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Secret"),
     *      @OA\Parameter(ref="#/components/parameters/X-Api-Token"),
     *      @OA\Parameter(ref="#/components/parameters/X-Requested-With"),
     *      @OA\Parameter(ref="#/components/parameters/include"),
     *      @OA\RequestBody(
     *         description="The CSV file",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Returns a reference to the file",
     *          @OA\Header(header="X-MINIMUM-CLIENT-VERSION", ref="#/components/headers/X-MINIMUM-CLIENT-VERSION"),
     *          @OA\Header(header="X-RateLimit-Remaining", ref="#/components/headers/X-RateLimit-Remaining"),
     *          @OA\Header(header="X-RateLimit-Limit", ref="#/components/headers/X-RateLimit-Limit"),
     *       ),
     *       @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError"),
     *
     *       ),
     *       @OA\Response(
     *           response="default",
     *           description="Unexpected Error",
     *           @OA\JsonContent(ref="#/components/schemas/Error"),
     *       ),
     *     )
     */
    public function preimport(PreImportRequest $request)
    {
        // Create a reference
        $hash = Str::random(32);

        $data = [
            'hash'     => $hash,
            'mappings' => [],
        ];
        /** @var UploadedFile $file */
        foreach ($request->files->get('files') as $entityType => $file) {
            $contents = file_get_contents($file->getPathname());
            // $contents = mb_convert_encoding($contents, 'UTF-16LE', 'UTF-8');

            // Store the csv in cache with an expiry of 10 minutes
            Cache::put($hash.'-'.$entityType, base64_encode($contents), 600);

            // Parse CSV
            $csv_array = $this->getCsvData($contents);

            $class_map = $this->getEntityMap($entityType);

            $data['mappings'][$entityType] = [
                'available' => $class_map::importable(),
                'headers'   => array_slice($csv_array, 0, 2),
            ];
        }

        return response()->json($data);
    }

    public function import(ImportRequest $request)
    {
        $data = $request->all();

        if (empty($data['hash'])) {
            // Create a reference
            $data['hash'] = $hash = Str::random(32);

            /** @var UploadedFile $file */
            foreach ($request->files->get('files') as $entityType => $file) {
                $contents = file_get_contents($file->getPathname());

                // Store the csv in cache with an expiry of 10 minutes
                Cache::put($hash.'-'.$entityType, base64_encode($contents), 600);
            }
        }

        unset($data['files']);
        CSVIngest::dispatch($data, auth()->user()->company());

        return response()->json(['message' => ctrans('texts.import_started')], 200);
    }

    private function getEntityMap($entity_type)
    {
        return sprintf('App\\Import\\Definitions\%sMap', ucfirst($entity_type));
    }

    private function getCsvData($csvfile)
    {
        if (! ini_get('auto_detect_line_endings')) {
            ini_set('auto_detect_line_endings', '1');
        }

        $csv = Reader::createFromString($csvfile);
        $stmt = new Statement();
        $data = iterator_to_array($stmt->process($csv));

        if (count($data) > 0) {
            $headers = $data[0];

            // Remove Invoice Ninja headers
            if (count($headers) && count($data) > 4) {
                $firstCell = $headers[0];

                if (strstr($firstCell, (string) config('ninja.app_name'))) {
                    array_shift($data); // Invoice Ninja...
                    array_shift($data); // <blank line>
                    array_shift($data); // Entity Type Header
                }
            }
        }

        return $data;
    }
}
