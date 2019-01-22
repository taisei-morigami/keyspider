<?php
/*******************************************************************************
 * Key Spider
 * Copyright (C) 2019 Key Spider Japan LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 ******************************************************************************/

namespace App\Http\Controllers;

use App\Exceptions\SCIMException;
use App\Http\Models\AAA;
use App\Http\Models\User;
use App\Http\Models\UserResource;
use App\Jobs\DBImporterFromScimJob;
use App\Ldaplibs\Import\ImportQueueManager;
use App\Ldaplibs\Import\ImportSettingsManager;
use App\Ldaplibs\Import\SCIMReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Optimus\Bruno\EloquentBuilderTrait;
use Optimus\Bruno\LaravelController;

class UserController extends LaravelController
{
    use EloquentBuilderTrait;

    const SCHEMAS_EXTENSION_USER = "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User";

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function welcome()
    {
        return view('welcome');
    }

    public function index(Request $request)
    {
        $fileIni = storage_path('ini_configs/import/UserInfoSCIMInput.ini');

        $filter = explode(' ', $request->input('filter'));

        $column = isset($filter[0]) && $filter[0] === 'userName' ? '001' : null;
        $filterValue = isset($filter[2]) ? $filter[2] : null;

        $pattern = '/([A-Za-z0-9\._+]+)@(.*)/';
        $isPattern = preg_match($pattern, $filterValue, $result);

        $valueQuery = null;
        if ($isPattern) {
            $valueQuery = $result[1];
        }

        $dataQuery = AAA::where($column, $valueQuery)->first();

        $dataFormat = [];
        if ($dataQuery) {
            $importSetting = new ImportSettingsManager();
            $dataFormat = $importSetting->formatDBToSCIMStandard($dataQuery->toArray(), $fileIni);
            $dataFormat['id'] = $dataFormat['userName'];
            unset($dataFormat[0]);
        }

        $jsonData = [];
        if (!empty($dataFormat)) {
            $data = [
                'id' => $dataFormat['userName'],
                "externalId" => $dataFormat['userName'],
                "userName" => str_replace("\"", "", $filterValue),
                "active" => true,
                "displayName" => $dataFormat['displayName'],
                "meta" => [
                    "resourceType" => "User",
                ],
                "name" => [
                    "formatted" => "",
                    "familyName" => "",
                    "givenName" => "",
                ],
                "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User" => [
                    "department" => $dataFormat['department']
                ],
            ];
            array_push($jsonData, $data);
        }

        return $this->response($this->toSCIMArray($jsonData), $code = 200);
    }

    /**
     * Create data
     *
     * @param Request $request
     * @return \Optimus\Bruno\Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function store(Request $request)
    {
        $dataPost = $request->all();
        $importSetting = new ImportSettingsManager();

        Log::info('-----------------creating user...-----------------');
        Log::info(json_encode($dataPost, JSON_PRETTY_PRINT));
        Log::info('--------------------------------------------------');

        $filePath = storage_path('ini_configs/import/UserInfoSCIMInput.ini');
        $setting = $importSetting->getSCIMImportSettings($filePath);

        // save user resources model
        $queue = new ImportQueueManager();
        $queue->push(new DBImporterFromScimJob($dataPost, $setting));

        return $this->response($dataPost, $code = 201);
    }

    /**
     * Show detail data
     *
     * @param $id
     */
    public function show($id)
    {
        // do something
    }

    /**
     * Update
     *
     * @param $id
     * @param Request $request
     * @return void
     * @throws SCIMException
     */
    public function update($id, Request $request)
    {
        // do something
        Log::info('-----------------PATCH USER...-----------------');
        Log::debug($id);
        Log::debug(json_encode($request->all(), JSON_PRETTY_PRINT));
        Log::info('--------------------------------------------------');

        $user = AAA::where('001', $id)->first();

        if (!$user) {
            throw (new SCIMException('User Not Found'))->setCode(400);
        }

        $filePath = storage_path('ini_configs/import/UserInfoSCIMInput.ini');

        $input = $request->input();

        if ($input['schemas'] !== ["urn:ietf:params:scim:api:messages:2.0:PatchOp"]) {
            throw (new SCIMException(sprintf(
                'Invalid schema "%s". MUST be "urn:ietf:params:scim:api:messages:2.0:PatchOp"',
                json_encode($input['schemas'])
            )))->setCode(404);
        }

        if (isset($input['urn:ietf:params:scim:api:messages:2.0:PatchOp:Operations'])) {
            $input['Operations'] = $input['urn:ietf:params:scim:api:messages:2.0:PatchOp:Operations'];
            unset($input['urn:ietf:params:scim:api:messages:2.0:PatchOp:Operations']);
        }

        foreach ($input['Operations'] as $operation) {
            if (strtolower($operation['op']) === 'replace') {
                $scimReader = new SCIMReader();
                $options = [
                    "path" => $filePath,
                    'operation' => $operation,
                ];
                $scimReader->updateReplaceSCIM($id, $options);
            }
        }

        throw (new SCIMException('Update success'))->setCode(200);
    }

    /**
     * Destroy user
     *
     * @param $id
     * @return \Optimus\Bruno\Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        Log::info('-----------------DELETE USER...-----------------');
        Log::debug($id);
        Log::info('--------------------------------------------------');

        $response = [
            'totalResults' => count([]),
            "itemsPerPage" => 10,
            "startIndex" => 1,
            "schemas" => [
                "urn:ietf:params:scim:api:messages:2.0:ListResponse"
            ],
            'Resources' => [],
        ];

        return $this->response($response);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function setFilterForRequest(Request &$request): bool
    {
        $filter = explode(' ', $request->input('filter'));

        try {
            $request["filter_groups"] = [
                0 =>
                    [
                        'filters' =>
                            [
                                0 =>
                                    [
                                        'key' => $filter[0],
                                        'value' => trim($filter[2], '"'),
                                        'operator' => $filter[1],
                                    ],
                            ],
                    ],
            ];
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    private function toSCIMArray($dataArray)
    {
        $arr = [
            'totalResults' => count($dataArray),
            "itemsPerPage" => 10,
            "startIndex" => 1,
            "schemas" => [
                "urn:ietf:params:scim:api:messages:2.0:ListResponse"
            ],
            'Resources' => $dataArray,
        ];
        return $arr;
    }
}
