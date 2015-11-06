<?php
/**
 * milkycode Joomla 1.5 reCaptcha plugin.
 * @author      Christian Hinz <christian@milkycode.com>
 * @category    plugins
 * @package     plugins_system
 * @copyright   Copyright (c) 2015 milkycode UG (http://www.milkycode.com)
 * @url         https://github.com/milkycode/joomla_recaptcha_j15
 */

require_once(dirname(__FILE__).'/recaptchalib.php');

class ReCaptcha
{
    var $_success;

    var $_error;

    var $_resp;

    var $_ajax = true;

    var $_submitted = false;

    var $_processed = false;

    var $_publicKey = '6Lf2-QQAAAAAAC5kQM5ChJfvRP1jZNvOn8kE590h';

    var $_privateKey = '6Lf2-QQAAAAAAFcse8UtCXQ82wW5fWG9koEQAktv';

    function &getInstance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new ReCaptcha();
        }

        return $instance;
    }

    function get($key, $default = '')
    {
        $inst =& ReCaptcha::getInstance();

        return $inst->_get($key, $default);
    }

    function _get($key, $default = '')
    {
        $key = '_'.$key;

        return isset($this->$key) ? $this->$key : $default;
    }

    function setKeys($public, $private)
    {
        $inst =& ReCaptcha::getInstance();
        $inst->_set('publicKey', $public);
        $inst->_set('privateKey', $private);
    }

    function setAjaxMode($mode = true)
    {
        $inst =& ReCaptcha::getInstance();
        $inst->_set('ajax', $mode);
    }

    function _set($key, $value)
    {
        $key = '_'.$key;
        $this->$key = $value;
    }

    function process()
    {
        $inst =& ReCaptcha::getInstance();
        $inst->_process();
    }

    function _process()
    {
        if ($this->_processed) {
            return;
        }

        if (JRequest::getVar("recaptcha_challenge_field")) {
            $this->_submitted = true;
            $this->_resp = recaptcha_check_answer(
                $this->_get('privateKey'),
                $_SERVER["REMOTE_ADDR"],
                JRequest::getVar("recaptcha_challenge_field"),
                JRequest::getVar("recaptcha_response_field")
            );
            $this->_success = $this->_resp->is_valid;
            if (!$this->_success) {
                $this->_error = $this->_resp->error;
            }
        }
        $this->_html = recaptcha_get_html($this->_get('publicKey'), $this->_get('error'), $this->_ajax);
        $this->_processed = true;
    }
}