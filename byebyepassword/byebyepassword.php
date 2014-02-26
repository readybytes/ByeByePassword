<?php
/**
* @package Joomla plugin for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
*
* @author Rimjhim
*/

defined('_JEXEC') or die;

jimport('joomla.mail.helper');
require_once dirname(__FILE__)."/helper.php";

class PlgSystemByeByePassword extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	public function onAfterRoute()
	{
		$option = Jfactory::getApplication()->input->get('option');
		
		//do nothing, if someone is logged-in
		if(Jfactory::getUser()->id && $option == 'plg_bbpass'){
			return Jfactory::getApplication()->redirect(JRoute::_(base64_decode(Jfactory::getApplication()->input->get('return'))));
		}
		
		
		$action = Jfactory::getApplication()->input->get('action');
		
		if($option != 'plg_bbpass'){
			return true;		
		}		

		//do action 
		if($action == 'loginregister'){
        	// Check for request forgeries
			JSession::checkToken('post') or jexit(JText::_('JINVALID_TOKEN'));    
			
			$this->loginRegister();
			
		}elseif($action == 'verifyToken'){
			$this->verifyLogin();
		}
		
		Jfactory::getApplication()->redirect(JRoute::_(base64_decode(Jfactory::getApplication()->input->get('return'))));
	}

	function verifyLogin()
	{
		$hash = Jfactory::getApplication()->input->get('hash');
		
		//this data contains userid + token
		$data = explode(';', base64_decode($hash));
		
		$user  = Jfactory::getuser($data[0]);
		if($user->id && $user->getParam('userToken',0) != $data[1]){
			return JFactory::getApplication()->enqueueMessage(JText::_("PLG_BBPASS_TOKEN_DOES_NOT_MATCH"));
		}
		
		$now   = Jfactory::getDate()->format('U');
		
		//check if link is still active
		if(($now-$user->getParam('generationTime',0)) < JFactory::getApplication()->getCfg('lifetime')*60){		
			$this->autoLogin($user);
		}
		else{
			return JFactory::getApplication()->enqueueMessage(JText::_("PLG_BBPASS_LOGIN_LINK_EXPIRED"));
		}
	}

	public function autoLogin($user)
	{
			// 	Get a database object
			$db	     	 = JFactory::getDBO();
			$query       = "SELECT * FROM #__users where `username`=".$db->Quote($user->username);

			$result      = $db->setQuery($query)->loadObject();

			$credentials = array('username'=>$user->username,'password'=> 'BLANK', 'password_clear'=>$result->password);
		 	$options     = array('user_id'=>$user->get('id'),'type'=>'bbpautologin', 'autoregister'=>false, 'user_record'=>$result);
			
		 	//add authentication plugin, so that we need not to create a different plugin for that 
		 	byebyepasswordHelper::setplugin();
		 	
			$app = JFactory::getApplication();
		 	$app->login($credentials,$options);
	}

   function loginregister()
   {
	   	$email = Jfactory::getApplication()->input->get('email');
	   	$msg   = '';
		$valid = true;
		
		if(!JMailHelper::isEmailAddress($email)){
			$msg = JText::_('PLG_BBPASS_INVALID_EMAIL');
			$valid = false;
		}

		if($valid){
			$db	   	   = JFactory::getDBO();
			$query     = "SELECT * FROM #__users where `email`=".$db->Quote($email);
			$result    = $db->setQuery($query)->loadObject();

			//if user is already exist then just send login link
			if(isset($result->id)){
				$user = Jfactory::getUser($result->id);
				//send activation link and login the user
				$this->sendLink($user);
				$msg = JText::_('PLG_BBPASS_LOGIN_LINK_SENT');
			}
			//register new user and send activation link
			else{
				$this->registerUser($email);
				$msg = JText::_('PLG_BBPASS_REGISTER_SUCCESS');
			}
		}
		
		//return to current url
		return JFactory::getApplication()->redirect(JRoute::_(base64_decode(Jfactory::getApplication()->input->get('currentUrl'))),$msg);
      }
   	
   	function registerUser($email)
   	{
		$password  = JUserHelper::genRandomPassword();
		$timestamp = Jfactory::getDate()->format('U');
		$temp      = array(	'username'=>$email,
		                    'name'=>$email,
		                    'email1'=>$email,
		                   	'email'=>$email,
						    'password1'=>$password,
							'password2'=>$password,
							'password'=>$password,
		                    'block'=>0);


		JFactory::getLanguage()->load('com_users');
		
		require_once  JPATH_ROOT.'/components/com_users/models/registration.php';
		// Initialise the table with JUser.
		$user  = new JUser;
		$model = new UsersModelRegistration();
		$data  = (array)$model->getData();
		
		// Merge in the registration data.
		foreach ($temp as $k => $v) {
			$data[$k] = $v;
		}

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
		
		//send login link
		$this->sendLink($user,true);
		
		return $user->id;
   	}

	function sendLink($user,$isRegistration = false)
	{
		$timestamp = Jfactory::getDate()->format('U');
		$token     = JApplication::getHash(JUserHelper::genRandomPassword());
		
		//create hash containing userid and token
		$hash = base64_encode($user->id.";".$token);
		
		// This checking is required for super users
		// as joomla doesn't allow to modify data of super users,directly
		if($user->authorise('core.admin')){
			$params    = json_decode($user->getparameters()->toString());
			$params->generationTime = $timestamp;
			$params->userToken      = $token;
			$newParams = json_encode($params);
			$db        = JFactory::getDbo();
			$query     = "update `#__users` set `params`='".$newParams."' where `id`=".$user->id; 
			$db->setQuery($query)->query();
		}
		else{
			$user->setParam('generationTime',$timestamp);
			$user->setParam('userToken',$token);
			$user->save();
		}

		$config  = JFactory::getConfig();
		$data    = $user->getProperties();
		$data['fromname']	= $config->get('fromname');
		$data['mailfrom']	= $config->get('mailfrom');
		$data['sitename']	= $config->get('sitename');
		$data['siteurl']	= JUri::base();
		
		//get return url
		$returnUrl = Jfactory::getApplication()->input->get('return',base64_encode('index.php'));
		
		
		
		$data['activate'] = JRoute::_($data['siteurl'].'index.php?option=plg_bbpass&action=verifyToken&hash='.$hash.'&return='.$returnUrl, false);
		
		//set body of mail
		if($isRegistration){
			$body = JText::sprintf("PLG_BBPASS_REGISTER_LINK",$data['activate']);
		}
		else{
			$body = JText::sprintf("PLG_BBPASS_LOGIN_LINK",$data['activate']);
		}
		
		$mailer = JFactory::getMailer();
        $mailer->IsHTML(true);

        //send link to login the user
		return  $mailer->setSender( array(
										$data['mailfrom'],
										$data['fromname']
									   ))
					    ->addRecipient($data['email'])
					    ->setSubject(JText::_('PLG_BBPASS_EMAIL_SUBJECT'))
					    ->setBody($body)
					    ->Send();
	}
}

class plgAuthenticationbbpAutoLogin extends JPlugin
{
	public function onUserAuthenticate($credentials, $options, $response)
	{
		if(isset($options['type']) && $options['type'] == 'bbpautologin'){
			self::_setResponse($options, $response);
			$response->status 	= JAuthentication::STATUS_SUCCESS;
		}
	}

	protected static function _setResponse($options, &$response)
	{
		$user = JUser::getInstance($options['user_id']);
		
		$response->email 			= $user->email;
		$response->fullname 		= $user->name;
		$response->username 		= $user->username;
		$response->language 		= $user->getParam('language');
		$response->error_message 	= '';
		$response->type				= 'bbpautologin';
	}
}
