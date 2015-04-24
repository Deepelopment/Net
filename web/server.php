<?php

use Deepelopment\Net\RPC;

require_once '../vendor/autoload.php';

class FooServer
{
    /**
     * Executing 'qqq' method callback
     *
     * @param array $params
     */
    public function execQqq(array $params = NULL)
    {
        return array('zzz');
    }
}
$fooServer = new FooServer;

$server = new RPC(
    'JSON',
    RPC::TYPE_SERVER
    #/*
    ,
    array(
        'request' => array(
            'jsonrpc' => '2.0',
            'method' => 'qqq',
        )
    )
    #*/
);
$layer = $server->getLayer();
$layer->bind('qqq', array($fooServer, 'execQqq'));
$layer->execute();
