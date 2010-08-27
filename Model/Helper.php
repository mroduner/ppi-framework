<?php

/**
 *
 * @version   1.0
 * @author    Paul Dragoonis <dragoonis@php.net>
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Digiflex Development
 * @package   PPI
 * @subpackage core
 */

class PPI_Model_Helper {

    private static $_instance = null;


	function __construct() {
//		$config = PPI_Registry::getInstance()->get('config');
//		parent::__construct($config->system->defaultUserTable, $config->system->defaultUserPK);
	}


    /**
     * The initialise function to create the instance
     * @return void
     */
    protected static function init() {
        self::setInstance(new PPI_Model_Helper());
    }

    /**
     * The function used to initially set the instance
     *
     * @param PPI_Model_Helper $instance
     * @throws PPI_Exception
     * @return void
     */
    static function setInstance(PPI_Model_Helper $instance) {
        if (self::$_instance !== null) {
            throw new PPI_Exception('PPI_Model_Helper is already initialised');
        }
        self::$_instance = $instance;
    }

    /**
     * Obtain the instance if it exists, if not create it
     *
     * @return PPI_Model_Helper
     */
    static function getInstance() {
        if (self::$_instance === null) {
            self::init();
        }
        return self::$_instance;
    }

	/**
	 * This function returns the role name of the user
	 * @return string
	 */
	static function getRoleType() {
		$aUserInfo = PPI_Model_Helper::getInstance()->getAuthData();
		return ($aUserInfo !== false && count($aUserInfo) > 0) ? $aUserInfo['role_name'] : 'guest';
	}


	/**
	 * This function returns the role number of the user
	 * @todo Do a lookup for the guest user ID instead of defaulting to 1
	 * @return integer
	 */
	static function getRoleID() {
	       $aUserInfo = PPI_Model_Helper::getInstance()->getAuthData();
	       return ($aUserInfo !== false && count($aUserInfo) > 0) ? $aUserInfo['role_id'] : 1;
	}

	static function getRoleNameFromID($p_iRoleID) {
		$oConfig = PPI_Helper::getConfig();
		$aRoles = array_flip(getRoles());
		if(array_key_exists($p_iRoleID, $aRoles)) {
			return $aRoles[$p_iRoleID];
		}
		throw new PPI_Exception('Unknown Role Type: '.$p_sRoleName);
	}


	function getRoleIDFromName($p_sRoleName) {
		$oConfig = PPI_Helper::getConfig();
		$aRoles = $oConfig->system->roleMapping->toArray();
		if(array_key_exists($p_sRoleName, $aRoles)) {
			return $aRoles[$p_sRoleName];
		}
		throw new PPI_Exception('Unknown Role Type: '.$p_sRoleName);
	}

	/**
	 * Function to recursively trim strings
	 * @param mixed $input The input to be trimmed
	 * @return mixed
	 */
	function arrayTrim($input){

    	if (!is_array($input)) {
	        return trim($input);
    	}

	    return array_map(array($this, 'arrayTrim'), $input);
	}

    /**
     * PPI Mail Sending Functioin
     * @param array $p_aOptions The options for sending to the mail library
     * @uses $p_aOptions[subject, body, toaddr] are all mandatory.
     * @uses Options available are toname
     * @return boolean The result of the mail sending process
     */
    static function sendMail(array $p_aOptions) {
		$oConfig = PPI_Helper::getConfig();
        $oEmail  = new PPI_Model_Email_Advanced();
        if(!isset($p_aOptions['subject'], $p_aOptions['body'], $p_aOptions['toaddr'])) {
            throw new PPI_Exception('Invalid parameters to sendMail');
        }

		$oEmail->Subject = $p_sSubject;
        if(isset($p_aOptions['fromaddr'], $p_aOptions['fromname'])) {
            $oEmail->SetFrom($p_aOptions['fromaddr'], $p_aOptions['fromname']);
        } elseif(isset($p_aOptions['fromaddr'])) {
            $oEmail->SetFrom($p_aOptions['fromaddr']);
        } else {
            $oEmail->SetFrom($oConfig->system->adminEmail, $oConfig->system->adminName);
        }

        if(isset($p_aOptions['toaddr'], $p_aOptions['toname'])) {
            $oEmail->AddAddress($p_aOptions['toaddr'], $p_aOptions['toname']);
        } elseif(isset($p_aOptions['toaddr'])) {
            $oEmail->AddAddress($p_aOptions['toaddr']);
        }

        if(isset($p_aOptions['altbody'])) {
            $oEmail->AltBody = $p_sMessage;
        }

		$oEmail->MsgHTML($p_aOptions['body']);

		// If the email sent successfully,
		return $oEmail->Send();

        // @todo - Log the email sending process.

    }

	/**
	 * Identify if an email is of valid syntax or not.
	 * @param string $p_sString The email address
	 * @return boolean
	 */
//	static function isValidEmail($p_sString) {
//		return preg_match("/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/", $p_sString) > 0;
//	}


} // End of class