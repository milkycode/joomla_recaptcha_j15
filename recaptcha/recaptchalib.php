<?php
/**
 * milkycode Joomla 1.5 reCaptcha plugin.
 * @author      Christian Hinz <christian@milkycode.com>
 * @category    plugins
 * @package     plugins_system
 * @copyright   Copyright (c) 2018 milkycode GmbH (http://www.milkycode.com)
 * @url         https://github.com/milkycode/joomla_recaptcha_j15
 *
 * This is a PHP library that handles calling reCAPTCHA.
 *    - Documentation and latest version
 *          https://developers.google.com/recaptcha/intro
 *    - Get a reCAPTCHA API Key
 *          https://www.google.com/recaptcha/admin
 *    - Discussion group
 *          http://groups.google.com/group/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The reCAPTCHA server URL's
 */
define("RECAPTCHA_API_SERVER", "https://www.google.com/recaptcha/api.js");
define("RECAPTCHA_VERIFY_SERVER", "www.google.com");
define("RECAPTCHA_VERIFY_PATH", "/recaptcha/api/siteverify");

/**
 * Encodes the given data into a query string format
 * @param $data - array of string elements to be encoded
 * @return string - encoded request
 */
function _recaptcha_qsencode($data)
{
    $req = "";
    foreach ($data as $key => $value) {
        $req .= $key.'='.urlencode(stripslashes($value)).'&';
    }

    // Cut the last '&'
    $req = substr($req, 0, strlen($req) - 1);

    return $req;
}

/**
 * Submits an HTTP POST to a reCAPTCHA server
 * @param string $host
 * @param string $path
 * @param array $data
 * @param int $port
 * @return array response
 */
function _recaptcha_http_post($host, $path, $data, $port = 443)
{
    $req = _recaptcha_qsencode($data);

    $http_request = "POST $path HTTP/1.0\r\n";
    $http_request .= "Host: $host\r\n";
    $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
    $http_request .= "Content-Length: ".strlen($req)."\r\n";
    $http_request .= "User-Agent: mcReCAPTCHA/PHP\r\n";
    $http_request .= "\r\n";
    $http_request .= $req;

    $response = '';
    if (false == ($fs = @fsockopen('ssl://'.$host, $port, $errno, $errstr, 10))) {
        die ('Could not open socket '.$errstr);
    }

    fwrite($fs, $http_request);

    while (!feof($fs)) {
        $response .= fgets($fs, 1160);
    } // One TCP-IP packet
    fclose($fs);
    $response = explode("\r\n\r\n", $response, 2);

    return $response;
}

$recaptcha_instances = 0;

/**
 * Gets the challenge HTML (javascript and non-javascript version).
 * This is called from the browser, and the resulting reCAPTCHA HTML widget
 * is embedded within the HTML form it was called from.
 * @param string $pubkey A public key for reCAPTCHA
 * @param boolean $ajax Explicit ReCaptcha Mode.
 * @return string - The HTML to be embedded in the user's form.
 */
function recaptcha_get_html($pubkey, $ajax = true)
{
    if ($pubkey == null || $pubkey == '') {
        die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha'>https://www.google.com/recaptcha</a>");
    }

    if ($ajax) {
        global $recaptcha_instances;
        $i = $recaptcha_instances++;
        $id = "recaptcha_instance_$i";

        return '<script type="text/javascript">
                  var onloadRecaptcha = function() {
                    grecaptcha.render(\''.$id.'\', {
                      \'sitekey\' : \''.$pubkey.'\'
                    });
                  };
                </script>
                <script src="'.RECAPTCHA_API_SERVER.'?onload=onloadRecaptcha&render=explicit" async defer></script>
                <div id="'.$id.'"></div>';
    } else {
        return '<script src="'.RECAPTCHA_API_SERVER.'" async defer></script>
                <div class="g-recaptcha" data-sitekey="'.$pubkey.'"></div>';
    }

}

/**
 * A ReCaptchaResponse is returned from recaptcha_check_answer()
 */
class ReCaptchaResponse
{
    var $is_valid;
    var $error;
}

/**
 * Calls an HTTP POST function to verify if the user's guess was correct
 * @param string $privkey
 * @param string $remoteip
 * @param string $response
 * @param array $extra_params an array of extra variables to post to the server
 * @return ReCaptchaResponse
 */
function recaptcha_check_answer($privkey, $remoteip, $response, $extra_params = array())
{
    if ($privkey == null || $privkey == '') {
        die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/'>https://www.google.com/recaptcha/</a>");
    }

    if ($remoteip == null || $remoteip == '') {
        die ("For security reasons, you must pass the remote ip to reCAPTCHA");
    }

    $recaptcha_response = new ReCaptchaResponse();

    //discard spam submissions
    if ($response == null || strlen($response) == 0) {
        $recaptcha_response->is_valid = false;
        $recaptcha_response->error = 'bad-request';

        return $recaptcha_response;
    }

    $response = _recaptcha_http_post(
        RECAPTCHA_VERIFY_SERVER,
        RECAPTCHA_VERIFY_PATH,
        array(
            'secret' => $privkey,
            'remoteip' => $remoteip,
            'response' => $response
        ) + $extra_params
    );

    $answers = json_decode($response[1]);
    if (!empty($answers) && $answers->success) {
        $recaptcha_response->is_valid = true;
    } else {
        $recaptcha_response->is_valid = false;
        $error = 'error-codes';
        $recaptcha_response->error = $answers->$error;
    }

    return $recaptcha_response;

}