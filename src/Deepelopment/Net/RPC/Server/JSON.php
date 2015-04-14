<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Server;

use \InvalidArgumentException;
use Deepelopment\Net\RPC\ServerLayer;
use Deepelopment\Net\RPC\ServerInterface;

/**
 * Remote Procedure Call JSON server layer,
 * see {@see Deepelopment\Net\RPC}.
 *
 * Based on {@see https://github.com/fguillot/JsonRPC}.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @todo    Batch execution
 * @todo    Valid JSON answers for known errors
 */
class JSON extends ServerLayer
{
    /**
     * Version of protocol
     */
    const JSON_RPC_VERSION = '2.0';

    /**
     * Request data
     *
     * @var array
     */
    protected $request;

    /**
     * @param  array  $options  Layer options, support 'envoronment' and 'request' keys
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if (isset($this->options['request'])) {
            $this->request = $this->options['request'];
        } else {
            $this->request = file_get_contents('php://input');
        }
        if (is_string($this->request)) {
            $this->request = json_decode($this->request, TRUE);
        }
        if (!is_array($this->request)) {
            throw new InvalidArgumentException('Malformed request');
        }
        if (
            !isset($this->request['jsonrpc']) ||
            !isset($this->request['method']) ||
            !is_string($this->request['method']) ||
            $this->request['jsonrpc'] !== self::JSON_RPC_VERSION ||
            (
                isset($this->request['params']) &&
                !is_array($this->request['params'])
            )
        ) {
            throw new InvalidArgumentException('Invalid JSON RPC request');
        }
    }

    /**
     * Authenticates users by login/password pairs.
     *
     * @return void
     */
    public function authenticateUsers(array $users)
    {
        try {
            parent::authenticateUsers($users);
        } catch (UnauthorizedAccessException $exception) {
            header('WWW-Authenticate: Basic realm="JsonRPC"');
            header('Content-Type: application/json');
            header('HTTP/1.0 401 Unauthorized');
            echo '{"error": "Authentication failed"}';
            exit;
        }
    }

    /**
     * IP based client restrictions.
     *
     * @param  array $hosts  Array of host IPs
     */
    public function restrictByIPs(array $hosts)
    {
        try {
            parent::authenticateUsers($hosts);
        } catch (IPRestrictionException $exception) {
            header('Content-Type: application/json');
            header('HTTP/1.0 403 Forbidden');
            echo '{"error": "Access Forbidden"}';
            exit;
        }
    }

    /**
     * Executes server method.
     *
     * @param  array   $options
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @return mixed
     */
    public function execute(array $options = array(), $resetOptions = FALSE)
    {
        if ($resetOptions) {
            $this->options = $options;
        } else {
            $this->options = $options + $this->options;
        }

        $response = $this->executeMethod(
            $this->request['method'],
            isset($this->request['params'])
            ? $this->request['params']
            : NULL
        );
        if (is_array($response)) {
            $response = array('result' => $response);
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Returns default options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        $result = array(
            'envoronment' => $_SERVER
        );

        return $result;
    }
}
