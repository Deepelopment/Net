<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC\Client;

use BadMethodCallException;
use InvalidArgumentException;
use RuntimeException;
use Deepelopment\Logger;
use Deepelopment\Net\Request;
use Deepelopment\Net\RPC\ClientInterface;
use Deepelopment\Net\RPC\ClientLayerNet;

/**
 * Remote Procedure Call JSON client layer,
 * see {@see Deepelopment\Net\RPC}.
 *
 * Based on {@see https://github.com/fguillot/JsonRPC}.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class JSON extends ClientLayerNet implements ClientInterface
{
    /**
     * Version of protocol
     */
    const JSON_RPC_VERSION = '2.0';

    /**
     * Executes remote server method.
     *
     * @param  string  $method
     * @param  array   $params
     * @param  array   $options       {@see
     *                                \Deepelopment\Net\Request::__construct()}
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @param  string  $url           Cusrom URL if differs from initialized
     * @return mixed
     */
    public function execute(
        $method,
        array $params = NULL,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        $request = $this->prepareRequest($method, $params);
        $response = $this->sendRequest($request, $options, $resetOptions, $url);

        return $response;
    }

    /**
     * Executes remote server batch.
     *
     * @param  array   $batch         Array containing arrays:
     *                                [
     *                                    'method' => ...(,
     *                                    'params' => array(,,,))
     *                                ]
     * @param  array   $options       {@see
     *                                \Deepelopment\Net\Request::__construct()}
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @param  string  $url           Cusrom URL if differs from initialized
     * @return mixed
     */
    public function executeBatch(
        array $batch,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        $request = array();
        foreach ($batch as $command) {
            $params = isset($command['params']) ? $command['params'] : NULL;
            $request[] = $this->prepareRequest($command['method'], $params);
        }
        $response = $this->sendRequest($request, $options, $resetOptions, $url);

        return $response;
    }

    protected function getDefaultOptions()
    {
        return
            array(
                CURLOPT_CUSTOMREQUEST => 'POST'
            );
    }

    /**
     * Patches response.
     *
     * @param  string &$response
     * @return void
     */
    protected function patchResponse(&$response)
    {
    }

    /**
     * Validates response.
     *
     * @param  string &$response
     * @return void
     */
    protected function validateResponse($response)
    {
        if (
            isset($response['code']) &&
            isset($response['message'])
        ) {
            $this->handleError($response);
        }
    }

    /**
     * Prepares request data using calling RPC method and parameters.
     *
     * @param  string $method
     * @param  array  $params
     * @return array
     */
    protected function prepareRequest($method, array $params = NULL)
    {
        $request = array(
            'jsonrpc' => self::JSON_RPC_VERSION,
            'method'  => $method,
            'id'      => mt_rand()
        );
        if (is_array($params)) {
            $request['params'] = $params;
        }

        return $request;
    }

    /**
     * Sends request to remote server.
     *
     * @param  array   $request
     * @param  array   $options
     * @param  bool    $resetOptions  Flag specifying to reset previous options
     * @param  string  $url
     * @return mixed
     */
    protected function sendRequest(
        array $request,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        $options['method'] = Request::METHOD_OTHER;
        $options[CURLOPT_POSTFIELDS] = json_encode($request);

        $this->logger->write(
            sprintf(
                "%s sending request to %s:\n%s",
                get_class($this),
                '' === $url ? $this->url : $url,
                print_r($request, TRUE)
            ),
            Logger::NOTICE
        );

        $response = $this->send('', $options, $resetOptions, $url);

        $decoded = json_decode($response, TRUE);
        if (!is_array($decoded)) {
            $this->logger->write(
                sprintf(
                    "%s: invalid response received:\n%s",
                    get_class($this),
                    var_export($response, TRUE)
                ),
                Logger::WARNING
            );
        }
        $this->logger->write(
            sprintf(
                "%s received response:\n%s",
                get_class($this),
                print_r($decoded, TRUE)
            ),
            Logger::NOTICE
        );

        $this->validateResponse($decoded);
        if (isset($decoded['error'])) {
            $this->validateResponse($decoded['error']);
        }
        $result =
            isset($decoded['result'])
            ? $decoded['result']
            : NULL;

        return $result;
    }

    /**
     * Throw an exception according the RPC error.
     *
     * @param  array $error
     * @return void
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function handleError(array $error)
    {
        $data = '';
        if(isset($error['data'])){
            $data .=
                '.' .
                (
                    is_string($error['data'])
                        ? ' ' . $error['data']
                        : "\n" . var_export($error['data'], TRUE)
                );
        }
        $this->logger->write(
            sprintf(
                "%s: error received:\n%s",
                get_class($this),
                print_r($error, TRUE)
            ),
            Logger::WARNING
        );

        switch ($error['code']) {
            case -32601:
                throw new BadMethodCallException(
                    $error['message'] . $data
                );
            case -32602:
                throw new InvalidArgumentException(
                    $error['message'] . $data
                );
            default:
                throw new RuntimeException(
                    $error['message'] . $data,
                    $error['code']
                );
        }
    }
}
