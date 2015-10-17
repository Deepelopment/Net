<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net;

/**
 * Request based on cURL library implementation.
 *
 * @package Deepelopment/Net
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class Request
{
    const METHOD_GET   =  1;
    const METHOD_POST  =  2;
    const METHOD_OTHER = -1;

    /**
     * Default options
     *
     * @var array
     */
    protected $defaultOptions = array(
        // cURL library options
        CURLOPT_HEADER         => FALSE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      =>
            'PHP Deepelopment Framework / Deepelopment\\Net\\Request',
        // Class cpecific options
        'resetCookiesAtStart'  => TRUE,
        'resetCookiesOnSend'   => FALSE,
        'resetCookesAtEnd'     => TRUE,
        'cookieFile'           => '',
    );

    /**
     * Options
     *
     * @var array
     */
    protected $options = array();

    /**
     * cURL handler
     *
     * @var resource
     */
    protected $handler;

    /**
     * Array containing cURL request error,
     *
     * @var  array
     * @link http://php.net/manual/en/function.curl-errno.php
     * @link http://php.net/manual/en/function.curl-error.php
     */
    protected $error;

    /**
     * @param array $options  Array of cURL and internal options,
     *                        see self::$defaultOptions
     * @param bool  $reset    Reset previous options
     */
    public function __construct(array $options = array(), $reset = FALSE)
    {
        $this->setOptions($this->defaultOptions);
        $this->setOptions($options, (bool)$reset);

        $this->handler = curl_init();
        $this->prepareCookieFile('resetCookiesAtStart');
    }

    public function __destruct()
    {
        curl_close($this->handler);
        $this->prepareCookieFile('resetCookiesAtEnd');
    }

    /**
     * Sets options.
     *
     * @param array $options  Array of cURL and internal options,
     *                        see {@see self::$defaultOptions}
     * @param bool  $reset    Reset previous options
     */
    public function setOptions(array $options = array(), $reset = FALSE)
    {
        $this->options =
            $reset
                ? $options
                : $options + $this->options;
    }

    /**
     * Sends request.
     *
     * @param  string       $url
     * @param  string|array $data
     * @param  int          $method  self::GET | self::POST
     * @return mixed  curl_exec() result
     * @limk   http://php.net/manual/en/function.curl-exec.php
     */
    public function send($url, $data = '', $method = self::METHOD_GET)
    {
        $this->prepareCookieFile('resetCookiesOnSend');
        $options = array();
        foreach (array_keys($this->options) as $key) {
            if (is_int($key)) {
                $options[$key] = $this->options[$key];
            }
        }
        if (
            isset($this->options['cookieFile']) &&
            '' !== $this->options['cookieFile']
        ) {
            $options = array(
                CURLOPT_COOKIEFILE => $this->options['cookieFile'],
                CURLOPT_COOKIEJAR  => $this->options['cookieFile'],
            );
        }
        curl_setopt_array($this->handler, $options);

        if (is_array($data)) {
            $data = http_build_query($data);
        }
        switch ($method) {
            case self::METHOD_GET:
                if ('' !== $data) {
                    $url .=
                        (FALSE === mb_strpos($url, '?') ? '?' : '&') .
                        $data;
                }
                curl_setopt($this->handler, CURLOPT_POST, FALSE);
                break;
            case self::METHOD_POST:
                curl_setopt($this->handler, CURLOPT_POST, TRUE);
                curl_setopt($this->handler, CURLOPT_POSTFIELDS, $data);
                break;
        }
        if ('' !== $url) {
            curl_setopt($this->handler, CURLOPT_URL, $url);
        }

        $result = curl_exec($this->handler);

        $this->error = array(
            'code'    => curl_errno($this->handler),
            'message' => curl_error($this->handler)
        );

        return $result;
    }

    /**
     * Returns cURL request info.
     *
     * @parm   int  $option
     * @return mixed
     * @limk   http://php.net/manual/en/function.curl-getinfo.php
     */
    public function getInfo($option = 0)
    {
        return $option ? curl_getinfo($this->handler, $option) : curl_getinfo($this->handler);
    }

    /**
     * Returns cURL error.
     *
     * @return array
     * @limk   http://php.net/manual/en/function.curl-errno.php
     * @limk   http://php.net/manual/en/function.curl-error.php
     */
    public function getError()
    {
        return $this->error;
    }

    protected function prepareCookieFile($mode)
    {
        if (
            isset($this->options['cookieFile']) &&
            '' !== $this->options['cookieFile'] &&
            file_exists($this->options['cookieFile']) &&
            !empty($this->options[$mode])
        ) {
            unlink($this->options['cookieFile']);
        }
    }
}
