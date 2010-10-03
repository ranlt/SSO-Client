<?php
require_once ('SforcePartnerClient.php');
require_once ('config.inc.php');

class Sso_Plugin_Salesforce_Communicate
{

    protected function _createClient()
    {
        $proxy = new stdClass;
        $proxy->host  = SF_PROXY_HOST;
        $proxy->port  = SF_PROXY_PORT;
        $proxy->login = SF_PROXY_LOGIN;
        $proxy->password = SF_PROXY_PASSWD;
        $client = new SforcePartnerClient();
        $client->createConnection(SF_WSDL_LOCATION, $proxy);
        $loginResult = $client->login(SF_USERNAME, SF_PASSWORD.SF_TOKEN);
        return $client;
    }


    public function allowAccess($userArray)
    {
        $client = $this->_createClient();
        $userData = array(
            'Username' => $userArray['username'],
            'FirstName' => $userArray['fullName'],
            'Email' => $userArray['username'],
            'Alias' => $userArray['username'],
            'IsActive' => 'true',
            'TimeZoneSidKey'=> SF_TIMEZONE,
            'LocaleSidKey' => SF_LANGUAGE,
            'EmailEncodingKey' => 'ISO-8859-1',
            'ProfileId' => PROFILE_ID,
            'LanguageLocaleKey' => SF_LANGUAGE
        );

        $sObject = new SObject();
        $sObject->fields = $userData;
        $sObject->type = 'User';
        $upsertResponse = $client->upsert("Username", array ($sObject));
        if (!$upsertResponse->success) {
            throw new Sso_Plugin_SalesforceException($upsertResponse);
        }
    }

    public function denyAccess($username)
    {
        $client = $this->_createClient();
        $userData = array(
            'Username' => $username,
            'IsActive' => 'false',
        );
        $sObject = new SObject();
        $sObject->fields = $userData;
        $sObject->type = 'User';
        $upsertResponse = $client->upsert("Username", array ($sObject));
        if (!$upsertResponse->success) {
            throw new Sso_Plugin_SalesforceException($upsertResponse);
        }
    }
}