<?php
/**
* @package Joomla Module for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
*
* @author Rimjhim
*/

defined('_JEXEC') or die;

// Include the login functions only once
require_once dirname(__FILE__).'/helper.php';


$type  	= ModByeByePasswordHelper::getType();
$return	= ModByeByePasswordHelper::getReturnURL($params, $type);
$user	= JFactory::getUser();
$layout = $params->get('layout', 'default');

// Logged users must load the logout sublayout
if (!$user->guest)
{
	$layout .= '_logout';
}

require JModuleHelper::getLayoutPath('mod_byebyepassword', $layout);
