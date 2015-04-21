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
 * see {@see Deepelopment\Net\RPC}, {@see Deepelopment\Net\RPC\Server\JSON}.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
interface ServerInterface
{
    /**
     * Method and callback binding.
     *
     * @param  string   $method
     * @param  callback $callback
     * @return void
     */
    public function bind($method, $callback);

    /**
     * Authenticates users by login/password pairs.
     *
     * @param  array $users
     * @return void
     */
    public function authenticateUsers(array $users);

    /**
     * IP based client restrictions.
     *
     * @param  array $hosts  Array of host IPs
     * @return void
     */
    public function restrictByIPs(array $hosts);

    /**
     * Processes request and returns response.
     *
     * @return mixed
     */
    public function execute(array $options = array(), $resetOptions = FALSE);

    /**
     * Returns passed request.
     *
     * @return mixed
     */
    public function getRequest();
}
