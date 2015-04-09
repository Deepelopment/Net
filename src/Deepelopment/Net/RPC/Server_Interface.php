<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

/**
 * Remote Procedure Call server layer interface,
 * see {@see Deepelopment\Net\RPC}, {@see Deepelopment\Net\RPC\Client\JSON}.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
interface Server_Interface
{
    /**
     * Opens connection to remote service.
     *
     * @param  string $url
     * @return void
     */
    public function open($url);

    /**
     * Executes remote server method.
     *
     * @param  string  $method
     * @param  array   $params
     * @param  array   $options
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @param  string  $url
     * @return mixed
     */
    public function execute(
        $method,
        array $params = NULL,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    );

    /**
     * Closes connection to remote service.
     *
     * @return void
     */
    public function close();
}
