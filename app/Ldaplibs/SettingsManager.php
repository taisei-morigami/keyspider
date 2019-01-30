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

namespace App\Ldaplibs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SettingsManager
{
    public const INI_CONFIGS = 'ini_configs';
    public const EXTRACTION_CONDITION = 'Extraction Condition';
    public const CSV_IMPORT_PROCESS_FORMAT_CONVERSION = 'CSV Import Process Format Conversion';
    public const EXTRACTION_PROCESS_BASIC_CONFIGURATION = 'Extraction Process Basic Configuration';
    public const CSV_IMPORT_PROCESS_BASIC_CONFIGURATION = 'CSV Import Process Basic Configuration';
    const GENERAL_SETTINGS_INI_PATH = 'ini_configs/GeneralSettings.ini';
    const ENCRYPT_STANDARD_METHOD = 'aes-256-cbc';
    public $iniMasterDBFile;
    public $masterDBConfigData;
    public $generalKeys;
    protected $keySpider;

    public function __construct($ini_settings_files = null)
    {
        if (!$this->validateKeySpider()) {
            $this->keySpider = null;
        } elseif ($this->isGeneralSettingsFileValid()) {
            $this->iniMasterDBFile = $this->keySpider['Master DB Configurtion']['master_db_config'];
            $this->masterDBConfigData = parse_ini_file($this->iniMasterDBFile, true);

            $this->isGeneralSettingsFileValid();
        }
    }

    /**
     * @return bool|null
     */
    public function validateKeySpider(): ?bool
    {
        try {
            $this->keySpider = parse_ini_file(
                storage_path(self::INI_CONFIGS . '/KeySpider.ini'),
                true);

            $validate = Validator::make($this->keySpider, [
                'Master DB Configurtion' => 'required',
                'CSV Import Process Configration' => 'required',
                'SCIM Input Process Configration' => 'required',
                'CSV Extract Process Configration' => 'required',
                'CSV Output Process Configration' => 'required'
            ]);
            if ($validate->fails()) {
                Log::error('Key spider INI is not correct!');
                throw new \RuntimeException($validate->getMessageBag());
            }
        } catch (\Exception $exception) {
            Log::error('Error on file KeySpider.ini');
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }

        try {
            $filesPath = parse_ini_file(storage_path(self::INI_CONFIGS .
                '/KeySpider.ini'));

            $allFilesInKeySpider = array_merge($filesPath['import_config'],
                $filesPath['extract_config'],
                [$filesPath['master_db_config']]);
            array_walk($allFilesInKeySpider,
                function ($filePath) {
                    if (!file_exists($filePath)) {
                        throw new \RuntimeException("[KeySpider validation error] The file: <$filePath> is not existed!");
                    }
                });
        } catch (\Exception $exception) {
            Log::error('Error on file KeySpider.ini');
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }
        return true;
    }

    private function isGeneralSettingsFileValid(): bool
    {
        try {
            $this->generalKeys = parse_ini_file(storage_path(self::GENERAL_SETTINGS_INI_PATH), true);
            $validate = Validator::make($this->generalKeys, [
                'KeySettings' => 'required',
                'KeySettings.Azure_key' => 'required',
                'KeySettings.Encryption_key' => 'required',
            ]);
            if ($validate->fails()) {
                throw new \Exception($validate->getMessageBag());
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }
        return true;
    }

    /** @noinspection MultipleReturnStatementsInspection */

    public function isFolderExisted($folderPath): bool
    {
        return is_dir($folderPath);
    }

    /**
     * @return Bearer token
     */
    public function getAzureADAPItoken()
    {
        return $this->generalKeys['KeySettings']['Azure_key'];
    }

    /**
     *
     * @param $data
     * @param $key
     * @return string encrypted already
     */
    public function passwordEncrypt($data, $key = null)
    {
        $encryptionKey = $key ? $key : $this->generalKeys['KeySettings']['Encryption_key'];
        // Generate an initialization vector
        $initializationVector = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::ENCRYPT_STANDARD_METHOD));
        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted = openssl_encrypt($data, self::ENCRYPT_STANDARD_METHOD,
            $encryptionKey,
            0,
            $initializationVector);

        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode($encrypted . '::' . $initializationVector);
    }

    /**
     * @param $data
     * * @param $key
     * @return string decrypted
     */
    public function passwordDecrypt($data, $key = null)
    {
        $encryptionKey = $key ? $key : $this->generalKeys['KeySettings']['Encryption_key'];
        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encryptedData, $initializationVector) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encryptedData,
            self::ENCRYPT_STANDARD_METHOD,
            $encryptionKey,
            0,
            $initializationVector);
    }

    protected function removeExt($file_name)
    {
        return preg_replace('/\\.[^.\\s]{3,4}$/', '', $file_name);
    }

    protected function contains($needle, $haystack): bool
    {
        return strpos($haystack, $needle) !== false;
    }


}
