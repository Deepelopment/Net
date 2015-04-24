<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use RuntimeException;
use BadMethodCallException;
use Deepelopment\Logger;
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
     * Request data
     *
     * @var mixed
     */
    protected $request;

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
            $this->logger->write(
                'Unauthorized access',
                Logger::WARNING
            );
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
            $this->logger->write(
                sprintf(
                    'IP restriction for %s',
                    $this->environment['REMOTE_ADDR']
                ),
                Logger::WARNING
            );
            throw new IPRestrictionException;
        }
    }

    /**
     * Returns passed request.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }


    /**
     * Executes method
     *
     * @param  mixed $method
     * @param  mixed $params
     * @return mixed
     * @throws BadMethodCallException
     */
    protected function executeMethod($method, array $params = NULL)
    {
        if (!isset($this->methods[$method])) {
            $message =
                sprintf(
                    "Method '%s' not found",
                    $method
                );
            $this->logger->write($message, Logger::WARNING);
            throw new BadMethodCallException($message);
        }
        $response = call_user_func($this->methods[$method], $params);

        $this->logger->write(
            sprintf("JSON Server response:\n%s", var_export($response, TRUE)),
            Logger::NOTICE
        );

        return $response;
    }
}
