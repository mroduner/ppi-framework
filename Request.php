<?php

/**
 *
 * @author    Paul Dragoonis <dragoonis@php.net>
 * @license   http://opensource.org/licenses/mit-license.php MIT
 * @package   Core
 * @link      www.ppiframework.com
 *
 */
class PPI_Request {

	/**
	 * Environment variables
	 *
	 * @var array|arrayAccess
	 */
	protected $_get = null;
	protected $_post = null;
	protected $_server = null;
	/**
	 * Remote vars cache for the getRemove() function
	 *
	 * @var array
	 */
	protected $_remoteVars = array(
		'ip'				=> '',
		'userAgent'			=> '',
		'browser'			=> '',
		'browserVersion'	=> '',
		'browserAndVersion'	=> ''
	);
	/**
	 * Vars cache for the is() function
	 *
	 * @var array
	 */
	protected $_isVars = array(
		'ajax'		=> null,
		'mobile'	=> null,
		'ssl'		=> null
	);
	/**
	 * Mapping fields for get_browser()
	 *
	 * @var array
	 */
	protected $_userAgentMap = array(
		'browser'				=> 'browser',
		'browserVersion'		=> 'version',
		'browserAndVersion'		=> 'parent'
	);
	/**
	 * The browser data from
	 *
	 * @var array|null
	 */
	protected $_userAgentInfo = null;
	/**
	 * The request method
	 *
	 * @var null|string
	 */
	protected $_requestMethod = null;
	/**
	 * The protocol being used
	 *
	 * @var null|string
	 */
	protected $_protocol = null;
	/**
	 * The full url including the protocol
	 *
	 * @var null|string
	 */
	protected $_url = null;
	/**
	 * The URI after the base url
	 *
	 * @var null|string
	 */
	protected $_uri = null;
	/**
	 * The quick keyval lookup array for URI parameters
	 *
	 * @var array
	 */
	protected $_uriParams = array();

	/**
	 * Constructor
	 *
	 * By default, it takes environment variables cookie, env, get, post, server and session
	 * from data collectors (PPI_Request_*)
	 *
	 * However, any of these can be overriden by an array or by an object that extends their
	 * representing PPI_Request_* class
	 *
	 * @param array $env Change environment variables
	 */
	function __construct(array $env = array()) {

		if (isset($env['server']) && (is_array($env['server']) || $env['server'] instanceof PPI_Request_Server)) {
			$this->_server = $env['server'];
		} else {
			$this->_server = new PPI_Request_Server();
		}

		if (isset($env['get']) && (is_array($env['get']) || $env['get'] instanceof PPI_Request_Get)) {
			$this->_get = $env['get'];
		} else {
			$this->_get = new PPI_Request_Get();
			$this->_get->setUri($this->getUri());
		}

		if (isset($env['post']) && (is_array($env['post']) || $env['post'] instanceof PPI_Request_Post)) {
			$this->_post = $env['post'];
		} else {
			$this->_post = new PPI_Request_Post();
		}
	}

	/**
	 * Obtain a url segments value pair by specifying the key.
	 * eg: /key/val/key2/val2 - by specifying key, you get val, by specifying key2, you get val2.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @return mixed
	 */
	function get($var, $default = null) {

		if (isset($_GET[$var])) {
			return urldecode(is_numeric($var) ? (int)$var : $var);
		}
		return isset($this->_get[$var]) ? $this->_get[$var] : $default;
	}

	/**
	 * Retrieve information passed via the $_POST array.
	 * Can specify a key and return that, else return the whole $_POST array
	 *
	 * @param string $key Specific $_POST key
	 * @param mixed $default null if not specified, mixed otherwise
	 * @return string|array Depending if you passed in a value for $p_sIndex
	 */
	function post($key = null, $default = null) {

		if($key === null) {
			return $this->_post->all();
		}
		return isset($this->_post[$key]) ? $this->_post[$key] : $default;
	}


	function server($key = null, $default = null) {

		if($key === null) {
			return $this->_server->all();
		}
		return isset($this->_server[$key]) ? $this->_server[$key] : $default;
	}

	/**
	 * Retrieve all $_POST elements that have a specific prefix
	 *
	 * @param string $sPrefix The prefix to get values with
	 * @return array
	 */
	function stripPost($p_sPrefix = '') {

		$aValues = array();
		if ($p_sPrefix !== '' && $this->is('post')) {
			$aPost = $this->post();
			$aPrefixKeys = preg_grep("/{$p_sPrefix}/", array_keys($aPost));
			foreach ($aPrefixKeys as $prefixKey) {
				$aValues[$prefixKey] = $aPost[$prefixKey];
			}
		}
		return $aValues;
	}

	/**
	 * Check whether a value has been submitted via post
	 *
	 * @param string $p_sKey The $_POST key
	 * @return boolean
	 */
	function hasPost($p_sKey) {
		return array_key_exists($p_sKey, $_POST);
	}

	/**
	 * Remove a value from the $_POST superglobal.
	 *
	 * @param string $p_sKey The key to remove
	 * @return boolean True if the value existed, false if not.
	 */
	function removePost($p_sKey) {

		if (isset($_POST[$p_sKey])) {
			unset($_POST[$p_sKey]);
			return true;
		}
		return false;
	}

	/**
	 * Add a value to the $_POST superglobal
	 *
	 * @param string $p_sKey The key
	 * @param mixed $p_mValue The value to set the key with
	 * @return void
	 */
	function addPost($p_sKey, $p_mValue) {
		$_POST[$p_sKey] = $p_mValue;
	}

	/**
	 * Wipe the $_POST superglobal
	 *
	 * @return void
	 */
	function emptyPost() {
		$_POST = array();
	}

	/**
	 * Series of request related boolean checks
	 *
	 * @param string $var
	 * @return bool
	 */
	function is($var) {

		$var = strtolower($var);
		switch ($var) {
			case 'ajax':
				if ($this->_isVars['ajax'] === null) {
					$this->_isVars['ajax'] = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
							&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] === 'xmlhttprequest');
				}
				return $this->_isVars['ajax'];

			case 'post':
			case 'get':
			case 'put':
			case 'delete':
			case 'head':
				return strtolower($this->getRequestMethod()) === $var;

			case 'mobile':
				if ($this->_isVars['mobile'] === null) {
					$this->_isVars['mobile'] = $this->isRequestMobile();
				}
				return $this->_isVars['mobile'];

			case 'https':
			case 'ssl':
				if ($this->_isVars['ssl'] === null) {
					$this->_isVars['ssl'] = $this->getProtocol() === 'https';
				}
				return $this->_isVars['ssl'];
		}
		return false; // So that all paths return a val
	}

	/**
	 * Get a value from the remote requesting user/browser
	 *
	 * @param string $var
	 * @return string
	 */
	function getRemote($var) {

		switch ($var) {

			case 'ip':
				return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

			case 'referer':
			case 'referrer':
				return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

			case 'userAgent':
				return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

			case 'domain':
				$url = parse_url($this->getUrl());
				return ifset($uri['host'], '');
				break;

			case 'subdomain':
				throw new PPI_Exception('Not yet developed');
				break;

			case 'browser':
			case 'browserVersion':
			case 'browserAndVersion':
				// @tbc
				break;
		}
		return ''; // So all code paths return a value
	}

	/**
	 * Get the current request uri
	 *
	 * @todo substr the baseurl
	 * @return string
	 */
	function getUri() {

		if (null === $this->_uri) {
			$this->_uri = PPI_Helper::getRegistry()->get('PPI::Request_URI', $this->_server['REQUEST_URI']);
		}
		return $this->_uri;
	}

	/**
	 * Get the current protocol
	 *
	 * @return string
	 */
	function getProtocol() {

		if (null === $this->_protocol) {
			$this->_protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
		}
		return $this->_protocol;
	}

	/**
	 * Get the current url
	 *
	 * @return string
	 */
	function getUrl() {

		if ($this->_url === null) {
			$this->_url = $this->getProtocol() . '://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
		}
		return $this->_url;
	}

	/**
	 * Is the current request a mobile request
	 *
	 * @todo see if there is an array based func to do the foreach and strpos
	 * @return boolean
	 */
	protected function isRequestMobile() {

		$mobileUserAgents = array(
			'iPhone', 'MIDP', 'AvantGo', 'BlackBerry', 'J2ME', 'Opera Mini', 'DoCoMo', 'NetFront',
			'Nokia', 'PalmOS', 'PalmSource', 'portalmmm', 'Plucker', 'ReqwirelessWeb', 'iPod', 'iPad',
			'SonyEricsson', 'Symbian', 'UP\.Browser', 'Windows CE', 'Xiino', 'Android'
		);
		$currentUserAgent = $this->getRemote('userAgent');
		foreach ($mobileUserAgents as $userAgent) {
			if (strpos($currentUserAgent, $userAgent) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the current request method
	 *
	 * @return string
	 */
	protected function getRequestMethod() {

		if (null === $this->_requestMethod) {
			$this->_requestMethod = $_SERVER['REQUEST_METHOD'];
		}
		return $this->_requestMethod;
	}

	/**
	 * Get the is vars
	 *
	 * @return array
	 */
	public function getIsVars() {
		return $this->_isVars;
	}
}
