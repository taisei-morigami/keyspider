<?php


namespace App\Ldaplibs\SCIM;


use App\Ldaplibs\SettingsManager;
use bjsmasth\Salesforce\Authentication\PasswordAuthentication;
use bjsmasth\Salesforce\Exception\SalesforceAuthentication;
use Faker\Factory;
use Illuminate\Support\Facades\Config;

class SCIMToSalesforce
{
    public function __construct()
    {
        $options = parse_ini_file(storage_path('ini_configs/GeneralSettings.ini'), true) ['Salesforce Keys'];
        $salesforce = new PasswordAuthentication($options);
        try {
            $salesforce->authenticate();
        } catch (SalesforceAuthentication $e) {
        }

        $access_token = $salesforce->getAccessToken();
        echo "\n";
        var_dump($access_token);
        echo "\n";

        $this->crud = new \bjsmasth\Salesforce\CRUD();
    }

    public function createUserWithData(array $data = null)
    {
        if ($data == null) {
            $data = json_decode(Config::get('schemas.createUser'));
        }
        try {
            return $this->crud->create('USER', $data);  #returns id
        } catch (\Exception $exception) {
            echo($exception->getMessage());
            return -1;
        }
    }

    public function createResource($resourceType, array $data = null)
    {
        $faker = Factory::create();
        $resourceType = strtolower($resourceType);

        if ($resourceType == 'user') {
            $dataSchema = json_decode(Config::get('schemas.createUser'), true);
            $data['IsActive'] = isset($data['IsActive']) && $data['IsActive'] ? false : true;
            $resourceType = 'USER';
            foreach ($dataSchema as $key => $value) {
                if (in_array($key, array_keys($data))) {
                    if ($key == 'Alias') {
                        $data[$key] = substr($data[$key], 0, 7);
                    }
                    $dataSchema[$key] = $data[$key];
                } elseif ($key == 'Alias') {
                    $dataSchema[$key] = $faker->text(8);
                } elseif ($key == 'LastName') {
                    $dataSchema[$key] = $faker->lastName;
                } elseif ($key == 'Email') {
                    $dataSchema[$key] = $faker->freeEmail;
                }
            }

        } elseif (($resourceType == 'role') || (strtolower($resourceType) == 'group')) {
            $dataSchema = json_decode(Config::get('schemas.createGroup'), true);
            foreach ($dataSchema as $key => $value) {
                if (in_array($key, array_keys($data))) {
                    if ($key == 'Alias') {
                        $data[$key] = substr($data[$key], 0, 7);
                    }
                    $dataSchema[$key] = $data[$key];
                }
            }
//                $dataSchema = json_decode(Config::get('schemas.createGroup'), true);
            $resourceType = 'GROUP';
        }
        echo("Create [$resourceType] with data: \n");
        echo(json_encode($dataSchema, JSON_PRETTY_PRINT));
        try {
            $response = $this->crud->create($resourceType, $dataSchema);
            echo "\nCreate user Response: [$response]\n";
            $this->updateGroupMemebers($resourceType, $data, $response);
            return $response;  #returns id
        } catch (\Exception $exception) {
//            var_dump($exception);
            echo($exception->getMessage());
            return $response;
        }
    }

    public function createGroupWithData(array $data = null)
    {
        if ($data == null) {
            $data = json_decode(Config::get('schemas.createGroup'));
        }
        try {
            return $this->crud->create('GROUP', $data);  #returns id
        } catch (\Exception $exception) {
            echo($exception->getMessage());
            return -1;
        }
    }

    public function getUser($id)
    {
        return $this->crud->getResourceDetail('Users', $id);
    }

    public function getGroup($id)
    {
        return $this->crud->getResourceDetail('Groups', $id);
    }

    public function getResourceDetails($resourceId, $resourceType)
    {
        $resourceType = $this->getResourceTypeOfSF($resourceType);
        return $this->crud->getResourceDetail($resourceType, $resourceId);
    }

    public function getUsersList()
    {
        return $this->crud->getResourceList('Users');
    }

    public function getGroupsList()
    {
        return $this->crud->getResourceList('Groups');
    }

    public function addMemberToGroup($memberId, $groupId)
    {
        try{
            return ($this->crud->addMemberToGroup($memberId, $groupId));
        }
        catch (\Exception $exception){
            return [];
        }

    }

    public function deleteResource($resourceType, $resourceId)
    {
        $resourceType = strtolower($this->getResourceTypeOfSF($resourceType, true));
        try {
            return ($this->crud->delete($resourceType, $resourceId));
        } catch (\Exception $exception) {
            var_dump("\n$exception");
            return false;
        }

    }

    public function updateResource($resourceType, $data)
    {
        $oriData = $data;
        $resourceType = strtolower($this->getResourceTypeOfSF($resourceType, true));
        $resourceId = $data['externalSFID'];
        unset($data['externalSFID']);
        if ($resourceType == 'user') {
//            $data['IsActive'] = $data['IsActive']?false:true;
            if (!isset($data['IsActive'])) {
                $data['IsActive'] = true;
            } else {
                $data['IsActive'] = $data['IsActive'] ? false : true;
            }

            $dataSchema = json_decode(Config::get('schemas.createUser'), true);
            $resourceType = 'USER';
            foreach ($data as $key => $value) {
                if (!in_array($key, array_keys($dataSchema))) {
                    unset($data[$key]);
                }
                if ($key == 'Alias') {
                    $data[$key] = substr($data[$key], 0, 7);
                }
            }
        } elseif (($resourceType == 'group') || ($resourceType == 'role')) {

            $dataSchema = json_decode(Config::get('schemas.createGroup'), true);
            foreach ($data as $key => $value) {
                if (!in_array($key, array_keys($dataSchema))) {
                    unset($data[$key]);
                }
            }
        }
        echo("\nUpdate $resourceType with data: \n");
        echo(json_encode($data, JSON_PRETTY_PRINT));

        $update = $this->crud->update($resourceType, $resourceId, $data);
        $this->updateGroupMemebers($resourceType, $oriData, $resourceId);
        echo("\nUpdate: $update");
        return $update;
    }

    /**
     * @param $resourceType
     * @return string
     */
    private function getResourceTypeOfSF($resourceType, $isREST = false): string
    {
        if ($resourceType == 'User') {
            $resourceType = $isREST ? 'User' : 'Users';
        } elseif ($resourceType == 'Role') {
            $resourceType = $isREST ? 'Group' : 'Groups';
        }
        return $resourceType;
    }

    public function getListOfGroupsUserBelongedTo($userAttibutes): array
    {
//        $memberOfSF = $this->getMemberOfsSF($userAttibutes['externalSFID']);
        //TODO: getListOfGroupsUserBelongedTo
        $memberOf = [];
        $roleMap = (new SettingsManager())->getRoleMapInExternalSFID('Role');
        foreach ($userAttibutes as $roleFlag => $value) {
            if ((strpos($roleFlag, 'RoleFlag-') !== false) && ($value == 1)) {
                $temp = explode('-', $roleFlag);
                $memberOf[] = $roleMap[(int)$temp[1]];

            }
        }
        return $memberOf;
    }


    public function getMemberOfsSF($uID)
    {
        $query = "SELECT userorgroupid From GROUPMEMBER where UserOrGroupId='$uID'";
        var_dump($this->crud->query($query));
        return [];
    }

    /**
     * @param $resourceType
     * @param array $data
     * @param $response
     */
    private function updateGroupMemebers($resourceType, array $data, $response): void
    {
        if (strtolower($resourceType) == 'user') {
            $memberOf = $this->getListOfGroupsUserBelongedTo($data);
            foreach ($memberOf as $groupID) {
                $addMemberResult = $this->addMemberToGroup($response, $groupID);
                echo "\nAdd member to group result:\n";
                var_dump($addMemberResult);
            }
        }
    }
}