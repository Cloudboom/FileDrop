<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\FileDrop\AppInfo\Application::APP_ID, 'main');

$urlGenerator = \OC::$server->getURLGenerator();
$CSRFToken = \OCP\Util::callRegister(); 

?>

<div id="filedrop">
    <div class="gui">
        <form action="<?php p($urlGenerator->linkToRoute('filedrop.mail.processUpload')); ?>"  method="post" enctype="multipart/form-data"> 
            <div class="headline">
                <br></br>
                <h1> <text>Filedrop</text> </h1>
                <br></br>
            </div>
            <div>
                <label for="data[]">Files:</label> <br>
                    <input name="data[]" type="file" multiple required>                 
            </div>
            <div>
                <label for="email">Recepients:</label><br>
                <input id="email" type="text" name="email" placeholder="E-mail adresses will be seperated by , or ;" size="40" required>
            </div>
            <div>
                <label for="subject">Subject:</label><br>
                <input id="subject" type="text" name="subject" placeholder="Subject" size="40" required>     
            </div>
            <div>
                <label for="message">Message:</label><br> 
                <textarea id="message" name="message" cols="200" rows="10" placeholder="Enter your message here"></textarea>
            </div>
            <div>
                <input type="hidden" name="requesttoken" value="<?php p($CSRFToken); ?>" />
                <label for="password">Password:</label><br>
                <input id="password" type="password" name="password" placeholder="password" size="15">
                <input type="submit">
            </div>        
        </form>
    </div>
</div>