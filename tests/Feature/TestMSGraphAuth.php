<?php

namespace Tests\Feature;

use Faker\Factory;
use Illuminate\Support\Facades\Config;
use Microsoft\Graph\Graph;
use Tests\TestCase;
use Microsoft\Graph\Model\User;

//use Faker\Generator as Faker;

class TestMSGraphAuth extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    private $accessToken;
    private $graph;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $tenantId = 'd40093bb-a186-4f71-8331-36cca3f165f8';
        $clientId = 'eb827075-42c3-4d23-8df0-ec135b46b5a6';
        $clientSecret = 'BtnF@kN3.?k.HA3raQBMasXiVOM3dNN0';
        $guzzle = new \GuzzleHttp\Client();
        $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/token?api-version=1.0';
        $token = json_decode($guzzle->post($url, [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'resource' => 'https://graph.microsoft.com/',
                'grant_type' => 'client_credentials',
            ],
        ])->getBody()->getContents());
        $this->accessToken = $token->access_token;
        $this->graph = new Graph();
        $this->graph->setAccessToken($this->accessToken);

    }

    public function testExample()
    {

        $users = $this->getUserList();
//        var_dump($users);
        $this->assertTrue(true);
    }

    /**
     * @param $accessToken
     * @return mixed
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    private function getUserList(): array
    {

        $users = $this->graph->createRequest("GET", "/users")
            ->setReturnType(User::class)
            ->execute();
        $this->assertNotNull($users);
        return $users;
    }

    public function testGetUser()
    {
        $user = $this->graph->createRequest("GET", "/users/tuanla@naljp.onmicrosoft.com")
            ->setReturnType(User::class)
            ->execute();
        var_dump($user);
        $this->assertNotNull($user->getUserPrincipalName());
    }

    public function testCreateUser()
    {
        $newUser = $this->createUserObject();
        $this->graph->createRequest("POST", "/users")
            ->attachBody($newUser)
            ->execute();

        //Get back to test
        $userCreated = $this->graph->createRequest("GET", "/users/".$newUser->getUserPrincipalName())->setReturnType(User::class)
            ->execute();
        //Check they're having same UserPrincipalName
        $newUserPrincipalName = $newUser->getUserPrincipalName();
        $createdPrincipalName = $userCreated->getUserPrincipalName();
        $this->assertEquals($newUserPrincipalName, $createdPrincipalName);
    }

    /**
     * @return User
     */
    private function createUserObject(): User
    {
        $faker = Factory::create();

        $userJson = Config::get('GraphAPISchemas.createUserJson');
        $newUser = new User(json_decode($userJson, true));
        $newUser->setDisplayName($faker->name);
        $userName = 'faker_' . $faker->userName;
        //        Required attributes
        $newUser->setGivenName($userName);
        $newUser->setMailNickname($userName);
        $newUser->setUserPrincipalName("$userName@naljp.onmicrosoft.com");
        //        Optional attributes
        $newUser->setCountry($faker->country);
        $newUser->setMobilePhone($faker->phoneNumber);
        $newUser->setStreetAddress($faker->streetAddress);
        return $newUser;
    }


}