<?php
/**
* @package Joomla plugin for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
*
* @author Rimjhim
*/

defined('_JEXEC') or die;


 class byebyepasswordHelper extends JPluginHelper
 {
 	static function setPlugin()
 	{
 		$obj = new stdClass();
 		$obj->type = 'authentication';
 		$obj->name = 'bbpautologin';
 		
 		parent::$plugins[] =  $obj;
 	}
 }
