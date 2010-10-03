<?php
class Sso_Plugin_SalesforceException extends Exception
{
    public $responseObject;

    public function __construct($response = NULL)
    {
        $this->responseObject = $response;
    }

}