<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

/**
 * Remote Procedure Call client layer abstract class.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class Client_Layer extends Layer
{
    /**
     * Remote service transport object
     *
     * @var mixed
     */
    protected $transport;

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Patches response.
     *
     * @param string &$response
     */
    abstract protected function patchResponse(&$response);

    /**
     * Validates response.
     *
     * @param mixed $response
     */
    abstract protected function validateResponse($response);
}
