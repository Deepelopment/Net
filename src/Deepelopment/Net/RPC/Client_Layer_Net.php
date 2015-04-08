<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net/RPC
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net\RPC;

use RuntimeException;
use Deepelopment\Net\Request;
use Deepelopment\Net\Utility;

/**
 * Remote Procedure Call client layer abstract class.
 *
 * Using \Deepelopment\Net\Request to connect to remote service.
 *
 * @package Deepelopment/Net/RPC
 * @author  deepeloper (https://github.com/deepeloper)
 */
abstract class Client_Layer_Net extends Client_Layer
{
    /**
     * @var \Deepelopment\Net\Request
     */
    protected $transport;

    /**
     * @var string
     */
    protected $url;

    public function open($url)
    {
        $this->transport = new Request($this->options);
        $this->url       = $url;
    }

    /**
     * @throws RuntimeException  In case of remote service returns not '200 OK'
     */
    public function execute(
        $method,
        array $params = NULL,
        array $options = array(),
        $resetOptions = FALSE,
        $url = ''
    )
    {
        if ('' === $url) {
            $url = $this->url;
        }
        $auth = '';
        $parsedURL = parse_url($url);
        if (isset($parsedURL['user'])) {
            $auth = $parsedURL['user'] . ':';
            if (isset($parsedURL['pass'])) {
                $auth .= $parsedURL['pass'];
            }
            unset($parsedURL['user'], $parsedURL['pass']);
            $url = Utility::buildUrl($parsedURL);
        }
        unset($parsedURL);
        if ('' !== $auth) {
            $options[CURLOPT_USERPWD] = $auth;
        }
        $this->transport->setOptions($options, $resetOptions);
        $response = $this->transport->send($url, $data, $options['method']);
        $code = $this->transport->getInfo(CURLINFO_HTTP_CODE);
        switch ($code) {
            case 200:
                break;
            default:
                if (
                    '' != $response &&
                    '{' == mb_substr($response, 0, 1, 'ASCII') &&
                    '}' == mb_substr($response, -1, NULL, 'ASCII')
                ){
                    break;
                }
                $error = $this->transport->getError();
                throw new RuntimeException(
                    $error['message'],
                    $error['code']
                );
        }
        $this->patchResponse($response);

        return $response;
    }

    public function close()
    {
        if ($this->transport) {
            $this->transport = NULL;
        }
    }
}
