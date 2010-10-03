<?php
class Sso_Plugin_SalesforceJobException extends Exception
{
    public $responseObject;

    public function __construct($response = NULL)
    {
        $this->responseObject = $response;
    }

}