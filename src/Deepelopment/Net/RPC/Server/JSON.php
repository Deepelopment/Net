<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Server;

use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use Deepelopment\Net\RPC\MethodExecutionException;
use Deepelopment\Core\Logger;
use Deepelopment\Net\RPC\ServerLayer;
use Deepelopment\Net\RPC\ServerInterface;

class InvalidJSONFormat extends Exception
{
}

class InvalidJSONRPCFormat extends Exception
{
}

/**
 * Remote Procedure Call JSON server layer,
 * see {@see Deepelopment\Net\RPC}.
 *
 * Based on {@see https://github.com/fguillot/JsonRPC}.<br /><br />
 * Example:
 * <code>
 * use Deepelopment\Net\RPC;
 *
 * $server = RPC::getLayer('JSON', RPC::TYPE_SERVER, $options);
 * $server->bind('command1', 'callback1');
 * // ...
 * $server->execute();
 *
 * function callback1(array $params = NULL)
 * {
 *     // ...
 * }
 * </code>
 * Pass next options structure to get exception details in response
 * (debug purpose only):
 * <code>
 * 'Deepelopment\\Net\\RPC\\Server\\JSON' => array(
 *      'returnExceptionError' => TRUE
 * )
 * </code>
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 * @todo    Batch execution
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
     * @var string
     */
    protected $optionsKey;

    /**
     * @param  array  $options  Layer options, support 'envoronment' and 'request' keys
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->optionsKey = get_class($this);

        if (isset($this->options['request'])) {
            $this->request = $this->options['request'];
        } else {
            $this->request = file_get_contents('php://input');
        }

        if (is_string($this->request)) {
            $this->request = json_decode($this->request, TRUE);
        }

        $this->logger->write(
            sprintf(
                "%s received request:\n%s",
                get_class($this),
                var_export($this->request, TRUE)
            )
        );
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

        try {
            $this->validateRequest();

            $response = $this->executeMethod(
                $this->request['method'],
                isset($this->request['params'])
                ? $this->request['params']
                : NULL
            );

        } catch (InvalidJSONFormat $exception) {
            $this->send(
                array(
                    'error' => array(
                        'code'    => -32700,
                        'message' => 'Parse error, invalid JSON format'
                    )
                )
            );
        } catch (InvalidJSONRPCFormat $exception) {
            $this->send(
                array(
                    'error' => array(
                        'code'    => -32600,
                        'message' => 'Invalid JSON RPC request'
                    )
                )
            );
        } catch (BadMethodCallException $exception) {
            $this->send(
                array(
                    'error' => array(
                        'code'    => -32601,
                        'message' => $exception->getMessage()
                    )
                )
            );
        }

        if (is_array($response) && empty($response['error'])) {
            $response = array('result' => $response);
        }

        $request = $this->getRequest();
        $requestId = isset($request['id']) ? (int)$request['id'] : NULL;

        $this->send($response, $requestId);
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

    /**
     * Send JSON response.
     *
     * @param  array  $response
     * @param  string $id
     * @return void
     */
    protected function send(array $response, $id = NULL)
    {
        header('Content-Type: application/json');
        $response['jsonrpc'] = self::JSON_RPC_VERSION;
        $response['id'] = $id;
        echo json_encode($response);
        exit;
    }

    /**
     * Validates request.
     *
     * @return void
     * @throws InvalidJSONFormat
     * @throws InvalidJSONRPCFormat
     */
    protected function validateRequest()
    {
        if (!is_array($this->request)) {
            throw new InvalidJSONFormat('Malformed request');
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
            throw new InvalidJSONRPCFormat('Invalid JSON RPC request');
        }
    }

    /**
     * Returns response according to exception thrown during method execution.
     *
     * @param  MethodExecutionException $exception
     * @return array
     */
    protected function onExceptionDuringExec(MethodExecutionException $exception)
    {
        $details = sprintf(
            "%s(): %s\n%s",
            __METHOD__,
            (string)$this->exceptionData,
            $exception->getTraceAsString()
        );
        $this->logger->write($details, Logger::WARNING);
        $aResponse = array(
            'error' => array(
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage()
            )
        );
        if(
            !empty($this->options[$this->optionsKey]) &&
            !empty($this->options[$this->optionsKey]['returnExceptionError'])
        ){
            $details = sprintf(
                "%s(): %s",
                __METHOD__,
                (string)$this->exceptionData
            );
            $aResponse['error']['data'] = $details;
        }

        return $aResponse;
    }
}
