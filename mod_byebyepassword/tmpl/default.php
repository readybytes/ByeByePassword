<?php
/**
* @package Joomla Module for byebye password
* @copyright Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
* @license GNU General Public License version 2 or later; see LICENSE
* @author Rimjhim
*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
<form id="login-register" method="post" action="index.php?option=plg_bbpass&action=loginregister&return=<?php echo base64_encode(Juri::getInstance()->toString()); ?>">

            <input type="email" placeholder="your@email.com" name="email" autofocus class="input-medium"/>
            <p><?php echo JText::_("MOD_BBPASS_LOGIN_LINK_HELP");?></p>

            <button type="submit" class="btn btn-primary"><?php echo JText::_("MOD_BBPASS_LOGIN_REGISTER");?></button>
		
		   <?php echo JHtml::_('form.token'); ?>
 </form>
