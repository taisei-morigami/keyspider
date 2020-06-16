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

namespace App\Ldaplibs\Import;

use App\Ldaplibs\SettingsManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use TablesBuilder;

class RDBImportSettingsManager extends SettingsManager
{
    /**
     * define const
     */
    public const RDB_IMPORT_PROCESS_CONFIGRATION = 'RDB Import Process Configration';
    /**
     * @var array
     */
    private $iniImportSettingsFiles = [];
    private $allTableSettingsContent;

    /**
     * ImportSettingsManager constructor.
     *
     * @param $iniSettingsFiles
     */
    public const RDB_INPUT_BASIC_CONFIGURATION = 'RDB Input Basic Configuration';

    public const RDB_INPUT_FORMAT_CONVERSION = 'RDB Input Format Conversion';

    public function __construct($iniSettingsFiles = null)
    {
        parent::__construct($iniSettingsFiles);
    }

    /**
     * Get rule of Import order and group by Schedule
     *
     * @return array
     */
    public function getScheduleImportExecution(): array
    {
        if ($this->keySpider === null) {
            Log::error('Wrong key spider! Do nothing.');
            return [];
        }
        $this->iniImportSettingsFiles = $this->keySpider[self::RDB_IMPORT_PROCESS_CONFIGRATION]['import_config'];

        $rule = $this->getRuleOfImport();

        $timeArray = array();
        foreach ($rule as $tableContents) {
            foreach ($tableContents[self::RDB_INPUT_BASIC_CONFIGURATION]['ExecutionTime'] as $specifyTime) {
                $filesArray['setting'] = $tableContents;
                $timeArray[$specifyTime][] = $filesArray;
            }
        }
        ksort($timeArray);
        return $timeArray;
    }

    /**
     * Get rule of Import without ordering by time execution
     *
     * @return array
     */
    private function getRuleOfImport(): array
    {
        (new TablesBuilder($this))->buildTables();

        if ($this->allTableSettingsContent) {
            return $this->allTableSettingsContent;
        }

        $master = $this->masterDBConfigData;

        $this->allTableSettingsContent = array();

        if (!$this->areAllImportIniFilesValid()) {
            return [];
        }

        foreach ($this->iniImportSettingsFiles as $iniImportSettingsFile) {
            $tableContents = parse_ini_file($iniImportSettingsFile, true);
            if ($tableContents === null) {
                Log::error('Can not run import schedule');
                return [];
            }
            // set filename in json file
            $tableContents['IniFileName'] = $iniImportSettingsFile;
            // Set destination table in database
            $tableNameInput = $tableContents[self::RDB_INPUT_BASIC_CONFIGURATION]['OutputTable'];

           // $masterDBConversion = $master[$tableNameInput];

            // Column conversion
            // $columnNameConversion = $tableContents[SettingsManager::RDB_INPUT_FORMAT_CONVERSION];
            // $tableContents[SettingsManager::RDB_INPUT_FORMAT_CONVERSION] = $columnNameConversion;
            $columnNameConversion = $tableContents[self::RDB_INPUT_FORMAT_CONVERSION];
            $tableContents[self::RDB_INPUT_FORMAT_CONVERSION] = $columnNameConversion;

            $this->allTableSettingsContent[] = $tableContents;
        }
        return $this->allTableSettingsContent;
    }

    /**
     * @return bool
     * <p>Check if all configure files are valid
     */
    private function areAllImportIniFilesValid(): bool
    {
        foreach ($this->iniImportSettingsFiles as $iniImportSettingsFile) {
            if (!$this->getIniFileContent($iniImportSettingsFile)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param $fileName
     * @return array|bool|null <p>array of key/value from ini file.</p>
     */
    private function getIniFileContent($fileName)
    {
        try {
            $iniArray = parse_ini_file($fileName, true);
            $isValid = $this->isImportIniValid($iniArray, $fileName);
            return $isValid ? $iniArray : null;
        } catch (\Exception $e) {
            Log::error(json_encode($e->getMessage(), JSON_PRETTY_PRINT));
            return null;
        }
    }

    /**
     * @param $iniArray
     * @param null $fileName
     * @return bool
     * <p>Check if a configure file are valid
     */
    private function isImportIniValid($iniArray, $fileName = null): bool
    {
        $rules = [
            self::RDB_INPUT_BASIC_CONFIGURATION => 'required',
            self::RDB_INPUT_FORMAT_CONVERSION => 'required'
        ];
        // Validate main keys
        $validate = Validator::make($iniArray, $rules);
        if ($validate->fails()) {
            $this->logErrorOfValidation($fileName, $validate);
            return false;
        }

        if ($validate->fails()) {
            $this->logErrorOfValidation($fileName, $validate);
            return false;
        }
        return true;
    }

    /**
     * @param $fileName
     * @param $validate
     */
    private function logErrorOfValidation($fileName, $validate): void
    {
        Log::error('Key error validation');
        Log::error(('Error file: ' . $fileName) ? $fileName : '');
        /** @noinspection PhpUndefinedMethodInspection */
        Log::info(json_encode($validate->getMessageBag(), JSON_PRETTY_PRINT));
    }

    /**
     * Based on MasterDBConf.ini, convert columns name from SCIM to our DB
     * @param $filePath
     * @return array|bool
     */
    private function getRDBInputFormatConversion($filePath)
    {
        $iniRDBSettingsArray = parse_ini_file($filePath, true);
        $tableNameInput = $iniRDBSettingsArray[self::RDBM_INPUT_BACIC_CONFIGURATION]['ImportTable'];
        $masterDBConversion = $this->masterDBConfigData[$tableNameInput];
        $columnNameConversion = $iniRDBSettingsArray[self::RDB_INPUT_FORMAT_CONVERSION];
        foreach ($columnNameConversion as $key => $value) {
            if (isset($masterDBConversion[$key])) {
                $columnNameConversion[$masterDBConversion[$key]] = $value;
                if($masterDBConversion[$key]!==$key)
                    unset($columnNameConversion[$key]);
            }
        }
        $iniRDBSettingsArray[self::RDB_INPUT_FORMAT_CONVERSION] = $columnNameConversion;
        return $iniRDBSettingsArray;
    }
}
