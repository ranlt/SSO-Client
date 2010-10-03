<?php
require_once ('SforcePartnerClient.php');
require_once ('config.inc.php');

class Sso_Plugin_Salesforce
{
    public $user;
    
    public function delegate($username)
    {
        if (SF_DELEGATE) {
            $jobClient = new GearmanClient();
            $jobClient->addServer();
            $this->setUser($username);
            if ($this->user->hasRight("read", "salesforce")) {
                $userArray = $this->user->getValues();
                if ($jobClient->doBackground("allow_access", serialize($userArray))) {
                    throw Sso_Plugin_SalesforceJobException;
                }
            } else {
                $userArray = $this->user->getValues();
                if ($jobClient->doBackground("deny_access", $username)) {
                    throw Sso_Plugin_SalesforceJobException;                }
            }
        } else {
            $this->setUser($username);
            if ($this->user->hasRight("read", "salesforce")) {
                $this->allowAccess();
            } else {
                $this->denyAccess($username);
            }
        }
    }

    public function setUser($username)
    {
        $this->user = new Sso_Model_User($username);
    }

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


    public function allowAccess($externalUser = NULL)
    {
        if (!is_null($externalUser)) {
            $this->user = $externalUser;
        }

        $client = $this->_createClient();
        $userArray = $this->user->getValues();
        
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

    public function denyAccess($externalUser)
    {
        if (!is_null($externalUser)) {
            $this->user = $externalUser;
        }
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