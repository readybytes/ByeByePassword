<?php
 class simpleregHelper extends JPluginHelper
 {
 	function setPlugin()
 	{
 		$obj = new stdClass();
 		$obj->type = 'authentication';
 		$obj->name = 'autologin';
 		
 		parent::$plugins[] =  $obj;
 	}
 }