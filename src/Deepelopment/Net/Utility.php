<?php
/**
 * PHP Deepelopment Framework
 *
 * @package Deepelopment/Net
 * @license Unlicense http://unlicense.org/
 */

namespace Deepelopment\Net;

/**
 * Net utilities.
 *
 * @package Deepelopment/Net
 * @author  deepeloper ({@see https://github.com/deepeloper})
 */
class Utility
{
    /**
     * Combine url from parts.
     *
     * @param  array $url
     * @return string
     * @link   http://php.net/manual/en/function.parse-url.php
     */
    static public function buildURL(array $url)
    {
        $result = '';
        if (isset($url['scheme'])) {
            $result = $url['scheme'] . '://';
        }
        if (isset($url['user'])) {
            $result .= $url['user'];
            if (isset($url['pass'])) {
                $result .= ':' . $url['pass'];
            }
            $result .= '@';
        }
        if (isset($url['host'])) {
            $result .= $url['host'];
        }
        if (isset($url['port'])) {
            $result .= ':' . $url['port'];
        }
        if ('' !== $result) {
           $result .= '/';
        }
        if (isset($url['path'])) {
            $result .= $url['path'];
        }
        if (isset($url['query'])) {
            $result .= '?' . $url['query'];
        }
        if (isset($url['fragment'])) {
            $result .= '#' . $url['fragment'];
        }

        return $result;
    }
}
