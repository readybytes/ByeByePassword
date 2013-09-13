<?php

/**
* @copyright	Copyright (C) 2009 - 2012 Ready Bytes Software Labs Pvt. Ltd. All rights reserved.
* @license		GNU/GPL, see LICENSE.php
* @package		Simple Reg
* @subpackage	Auto Login
*/
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

/**
 * Auto Login After Register Plugin
 *
 * @author Rimjhim
 */
jimport('joomla.user.authentication');

   // If simplereg-system plugin is disable then do nothing
   $state = JPluginHelper::isEnabled('system','simplereg');
   if(!$state){
       return;
   }

class plgAuthenticationAutoLogin extends JPlugin
{
	// In Joomla 2.5 this event is triggered
	public function onUserAuthenticate($credentials, $options, $response)
	{
		if(isset($options['type']) && $options['type'] == 'simplereg'){
			self::_setResponse($options, $response);
			$response->status 	= JAuthentication::STATUS_SUCCESS;
		}
	}

	protected static function _setResponse($options, &$response)
	{
		$user = JUser::getInstance($options['user_id']); // Bring this in line with the rest of the system
		$response->email 			= $user->email;
		$response->fullname 	= $user->name;
		$response->username 	= $user->username;
		$response->language 	= $user->getParam('language');
		$response->error_message = '';
		//fix for j35
		$response->type		= 'autologin';
	}

}