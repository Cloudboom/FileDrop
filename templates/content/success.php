<?php
//
?>

<div class="all">
    <div class= "headline2">
        <h2>Ihre Daten wurden hochgeladen</h2>
    </div>

    <div class="valid">
        <h3>An folgende Adressen wurde eine E-Mail geschickt:</h3>
        <ul style="list-style-type:none;">
            <?php foreach($_['validEmails'] as $singleEmail){ ?>
            <li> 
                <?php p($singleEmail); ?> 
            </li> 
            <?php } ?>
        </ul>
    </div>

    <div class="invalid">
        <h3>An folgende Adressen wurde keine E-Mail geschickt:</h3>
        <ul style="list-style-type:none;">
            <?php foreach($_['invalidEmails'] as $singleEmail){ ?>
            <li> 
                <?php p($singleEmail); ?> 
            </li> 
            <?php } ?>
        </ul>
    </div>
<!--
    <div class="errors">
        <h3>Errors:</h3>
        <ul style="list-style-type:none;">
            <li> 
                 <?php //p($_['error']) ?>
            </li> 
        </ul>
    </div> -->
</div>