<?php
/**
 * @package     Joomla.site
 * @subpackage  plg_simplereg
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class PlgSystemSimpleReg extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	public function onAfterRoute()
	{
		//do nothing, if someone is logged-in
		if(Jfactory::getUser()->id){
			return true;
		}
		
		$option = JRequest::getVar('option');
		$task   = JRequest::getVar('task');
		
		if($option != 'com_simplereg'){
			return true;		
		}		

		//do task 
		if($task == 'loginregister'){
			$this->loginRegister();
			
		}elseif($task == 'verifyToken'){
			$this->verifyLogin();
		}
		
		Jfactory::getApplication()->redirect(base64_decode(JRequest::getVar('return')));
	}

	function verifyLogin()
	{
		$token = JRequest::getVar('token');
		
		// Get the user id based on the token.
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__users'))
			->where($db->quoteName('activation') . ' = ' . $db->quote($token));
		$db->setQuery($query);

		$userId = $db->loadResult();
		
		if(empty($userId)){
			return true;
		} 
		
		$user  = Jfactory::getuser($userId);
		$now   = Jfactory::getDate()->format('U');
		
		if(($now-$user->getParam('generationTime',0)) < JFactory::getApplication()->getCfg('lifetime')*60){		
			$this->autoLogin($user);
		}
	}

	public function autoLogin($user)
	{
			// 	Get a database object
			$db	     = JFactory::getDBO();
			$query       = "SELECT * FROM #__users where `username`=".$db->Quote($user->username);

			$result      = $db->setQuery($query)->loadObject();

			$credentials = array('username'=>$user->username,'password'=> 'BLANK', 'password_clear'=>$result->password);
		 	$options     = array('user_id'=>$user->get('id'),'type'=>'simplereg', 'autoregister'=>false, 'user_record'=>$result);
			
		 	//add authentication plugin, so that we need not to create a different plugin for that 
		 	simpleregHelper::setplugin();
		 	
			$app = JFactory::getApplication();
		 	$app->login($credentials,$options);
	}

   function loginregister()
   {
	   	$email = JRequest::getVar('email');
	   	$msg   = '';
		$valid = true;
	
		jimport('joomla.mail.helper');
		
		if(!JMailHelper::isEmailAddress($email)){
			$msg = "Invalid email";
			$valid = false;
		}

		if($valid){
			$db	   = JFactory::getDBO();
			$query     = "SELECT * FROM #__users where `email`=".$db->Quote($email);
			$result    = $db->setQuery($query)->loadObject();

			if(isset($result->id)){
				$user = Jfactory::getUser($result->id);
				//send activation link and login the user
				$this->sendLink($user);
			}
			else{
				//register new user and send activation link
				$this->registerUser($email);
			}
		}
      }
   	
   	function registerUser($email)
   	{
   		// load user helper
		jimport('joomla.user.helper');
		$password  = JUserHelper::genRandomPassword();
		$timestamp = Jfactory::getDate()->format('U');
		$token     = JApplication::getHash($password);
		$temp      = array(	'username'=>$email,'name'=>$email,'email1'=>$email,
						'password1'=>$password, 'password2'=>$password, 'block'=>0);
				
		$config = JFactory::getConfig();

		require_once  JPATH_ROOT.'/components/com_users/models/registration.php';
		
		$model = new UsersModelRegistration();
		JFactory::getLanguage()->load('com_users');
		
		// Initialise the table with JUser.
		$user  = new JUser;
		$model = new UsersModelRegistration();
		$data  = (array)$model->getData();
		// Merge in the registration data.
		foreach ($temp as $k => $v) {
			$data[$k] = $v;
		}

		// Prepare the data for the user object.
		$data['email']		= $data['email1'];
		$data['password']	= $data['password1'];
	

		jimport('joomla.user.helper');
		$data['activation'] = $token;

		// Bind the data.
		if (!$user->bind($data)) {
			return false;
		}

		// Load the users plugin group.
		JPluginHelper::importPlugin('user');

		// Store the data.
		if (!$user->save()) {
			return false;
		}
		
		$this->sendLink($user);
   	}

	function sendLink($user)
	{
		$timestamp = Jfactory::getDate()->format('U');
		$token     = JApplication::getHash(JUserHelper::genRandomPassword());
		
		$user->set('activation',$token);
		$user->setParam('generationTime',$timestamp);
		$user->save();

		$config  = JFactory::getConfig();
		$data    = $user->getProperties();
		$data['fromname']	= $config->get('fromname');
		$data['mailfrom']	= $config->get('mailfrom');
		$data['sitename']	= $config->get('sitename');
		$data['siteurl']	= JUri::base();
		
		$returnUrl = JRequest::getVar('return','index.php');
		
		$data['activate'] = JRoute::_($data['siteurl'].'index.php?option=com_simplereg&task=verifyToken&token='.$token.'&return='.$returnUrl, false);
		
		$return = JFactory::getMailer()->setSender( array(
														$data['mailfrom'],
														$data['fromname']
													   ))
									   ->addRecipient($data['email'])
									   ->setSubject("Test simple registration")
									   ->setBody($data['activate'])
									   ->Send();
	}
}

class plgAuthenticationAutoLogin extends JPlugin
{
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
