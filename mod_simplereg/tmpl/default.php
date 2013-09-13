<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
<form id="login-register" method="post" action="index.php?option=com_simplereg&task=loginregister&return=<?php echo base64_encode(Juri::getInstance()->toString()); ?>">

            <h3>Login or Register</h3>

            <input type="text" placeholder="your@email.com" name="email" autofocus class="input-medium"/>
            <p>Enter your email address above and we will send <br />you a login link.</p>

            <button type="submit" class="btn btn-primary">Login / Register</button>

            <span></span>

 </form>
