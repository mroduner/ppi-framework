<?php
abstract class PPI_Dispatch_Abstract {

	/**
	 * The Config object
	 * @var object
	 */
	protected $_config;

	/**
	 * The Input object
	 * @var object
	 */
	protected $_input;

	/**
	 * The Full Url
	 * @var string
	 */
	protected $_fullUrl;

	/**
	 * The current controller class
	 *
	 * @var object
	 */
	protected $_controller;

	/**
	 * The curent method on our controller
	 *
	 * @var string
	 */
	protected $_method;

	/**
	 * The controller class name that was executed
	 *
	 * @var string
	 */
	protected $_controllerName;

	/**
	 * The router object
	 *
	 * @var object
	 */
	protected $_router;

	function __construct() {
		$this->_config = PPI_Helper::getConfig();
		$this->_input  = PPI_Helper::getInput();
		$this->_fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http') .
			'://' . str_replace('www.', '', $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Checks to theee if the base url has been misconfigured or not
	 * Will return true if there is indeed a bad base url match.
	 * Will return false if there is not bad base url match.
	 * @return boolean
	 */
	function checkBadBaseUrl() {
        return stripos($this->_fullUrl, $this->_config->system->base_url) === false;
	}

	/**
	 * Get the URI segments after the base url
	 * @todo Add a 3rd param to explode() to make it faster
	 * @return array
	 */
	function getURISegments() {
		$url = '';
		if($this->_router !== null) {
			$url = $this->_router->getRoute();
		}
		if($url == '') {
			$url = trim(str_replace($this->_config->system->base_url, '', $this->_fullUrl), '/');
		}
		return explode('/', $url);
	}

	/**
	 * Checks if a controller exists, if so - dispatch it otherwise return false
	 * @todo check if ($_SERVER ["REQUEST_URI"] == "/") {
	 * @return boolean
	 */
	function checkControllers() {

		// See if the mastercontroller exists in the config
		if(!isset($this->_config->system->masterController)) {
			throw new PPI_Exception('Unable to find mastercontroller in general.ini configuration file');
		}
		$sMasterController    = $this->_config->system->masterController;
		// If the mastercontroller is needed.
		$aUrls                = $this->getURISegments();
		$sControllerName      = ucfirst((empty($aUrls) || $aUrls[0] == '') ? $sMasterController : $aUrls[0]);
		$sLowerControllerName = strtolower($sControllerName);
		// Subtract the BaseUrl from the actual full URL and then what we have left is our controllers..etc
		$sContFilename = 'APP_Controller_' . $sControllerName; // eg: APP_Controller_User
		if(class_exists($sContFilename)) {

			// We can't instantiate abstract controllers (eg: shared controllers)
			$oReflectionClass = new ReflectionClass($sContFilename);
			if($oReflectionClass->isAbstract() === true) {
				return false;
			}

			// Proceed with instantiation.
			$oController = new $sContFilename();

			// Did we specify a method ?
			if( ($sMethod = $this->_input->get($sLowerControllerName)) != '') {
				// Does our method exist on the class
				if(!in_array($sMethod, get_class_methods(get_class($oController)))) {
					return false;
				}
			} else {
				$sMethod = 'index';
			}
			$this->setControllerName($sLowerControllerName);
			$this->setController($oController);
			$this->setMethod($sMethod);
			return true;
		}
		return false;
	}

	/**
	 * Set the controller class
	 *
	 * @param object $p_oController
	 */
	function setController($p_oController) {
		$this->_controller = $p_oController;
	}

	/**
	 * Set the controller class name
	 *
	 * @param string $p_sController
	 */
	function setControllerName($p_sController) {
		$this->_controllerName = $p_sController;
	}

	/**
	 * Get the controller class name
	 *
	 * @return string
	 */
	function getControllerName() {
		return $this->_controllerName;
	}

	/**
	 * Get the controller class
	 *
	 * @return object
	 */
	function getController() {
		return $this->_controller;
	}

	/**
	 * Set the method for the chosen controller
	 *
	 * @param string $p_sMethod
	 */
	function setMethod($p_sMethod) {
		$this->_method = $p_sMethod;

	}

	/**
	 * Get the method for the chosen controller
	 *
	 * @return string
	 */
	function getMethod() {
		return $this->_method;

	}

}