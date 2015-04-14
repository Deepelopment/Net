<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use RuntimeException;
use BadFunctionCallException;
use Deepelopment\Net\UnauthorizedAccessException;
use Deepelopment\Net\IPRestrictionException;

/**
 * Remote Procedure Call server layer abstract class.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
abstract class ServerLayer extends Layer implements ServerInterface
{
    /**
     * Server environment variables
     *
     * @var array
     */
    protected $environment;

    /**
     * Array containig methods as keys and callbacks as values
     *
     * @var array
     */
    protected $methods = array();

    /**
     * @param array  $options  Layer options
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->environment =
            isset($this->options['environment'])
            ? $this->options['environment']
            : array();
    }

    /**
     * Method and callback binding.
     *
     * @param  string   $method
     * @param  callback $callback
     * @return void
     * @throws RuntimeException
     */
    public function bind($method, $callback)
    {
        if (is_callable($callback)) {
            $this->methods[$method] = $callback;
        } else {
            throw new RuntimeException('Invalid callback passed');
        }
    }

    /**
     * Authenticates users by login/password pairs.
     *
     * @param  array $users
     * @return void
     * @throws UnauthorizedAccessException
     */
    public function authenticateUsers(array $users)
    {
        if (
            !isset($this->environment['PHP_AUTH_USER']) ||
            !isset($users[$this->environment['PHP_AUTH_USER']]) ||
            $users[$this->environment['PHP_AUTH_USER']] !== $this->environment['PHP_AUTH_PW']
        ) {
            throw new UnauthorizedAccessException;
        }
    }

    /**
     * IP based client restrictions.
     *
     * @param  array $hosts  Array of host IPs
     * @throws IPRestrictionException
     */
    public function restrictByIPs(array $hosts)
    {
        if (!in_array($this->environment['REMOTE_ADDR'], $hosts)) {
            throw new IPRestrictionException;
        }
    }

    /**
     * Executes method
     *
     * @param  mixed $method
     * @param  mixed $params
     * @return mixed
     * @throws BadFunctionCallException
     */
    protected function executeMethod($method, array $params = NULL)
    {
        if (!isset($this->methods[$method])) {
            throw new BadFunctionCallException("Method '{$method}' not found");
        }
        $result = call_user_func($this->methods[$method], $params);

        return $result;
    }
}
