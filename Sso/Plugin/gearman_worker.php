<?php
require_once ('Salesforce_Communicate.php');

$worker= new GearmanWorker();
$worker->addServer();
$worker->addFunction("allow_access", "allowAccess");
$worker->addFunction("deny_access", "denyAccess");

while ($worker->work());

function allowAccess($job)
{
    $work = $job->workload();
    $user = unserialize($work);
    try {
        $sf = getSalesforceInstance();
        $sf->allowAccess($user);
    } catch (Exception $e) {
        return FALSE;
    }
    return TRUE;
}

function denyAccess($job)
{
    $user = $job->workload();
    try {
        $sf = getSalesforceInstance();
        $sf->denyAccess($user);
    } catch (Exception $e) {
        return FALSE;
    }
    return TRUE;
}

function getSalesforceInstance()
{
    $sf = new Sso_Plugin_Salesforce_Communicate;
    return $sf;
}