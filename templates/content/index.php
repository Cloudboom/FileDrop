
<?php 
    $urlGenerator = \OC::$server->getURLGenerator();
    $CSRFToken = \OCP\Util::callRegister(); 
?>
        <div class="gui">
            <form action="<?php p($urlGenerator->linkToRoute('cload.mail.processUpload')); ?>"  method="post" enctype="multipart/form-data"> 
            <script src="passwordcheck.js"> </script>
                <div class="headline">
                    <br></br>
                    <h1> <text style="color:#ed1c24;">c</text><text style="color:000000;">Load</text> </h1>
                    <br></br>
                </div>
                <div>
                    <label for="data[]">Datei: </label> <br>
                        <input name="data[]" type="file" multiple required>  
                </div>
                <div>
                    <label for="email">Empfänger E-Mail:  </label><br>
                    <input id="email" type="text" name="email" placeholder="E-Mail Adressen werden durch , oder ; getrennt" size="40" required>
                </div>
                <div>
                    <label for="subject">Betreff:   </label>  <br>
                    <input id="subject" type="text" name="subject" placeholder="Betreff" size="40" required>     
                </div>
                <div>
                    <label for="message">Nachricht:      </label>   <br> 
                    <textarea id="message" name="message" cols="200" rows="10" placeholder="Hier eine Nachricht einfügen"></textarea>
                </div>
                <div>
                    <input type="hidden" name="requesttoken" value="<?php p($CSRFToken); ?>" />
                    <label for="password">Passwort:    </label>  <br>
                    <input id="password" type="password" name="password" placeholder="Passwort" size="15"> <!--  onkeyup="char_count();"-->
                    <input type="submit">
                </div>
           <!--     <div class= "check">
                    <p>Ihr Passwort ist:
                        <span id="feedback"></span>
                    </p>
                </div> -->
            </form>
        </div>