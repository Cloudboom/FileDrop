<?php
//
?>

<div class="all">
    <div class= "headline2">
        <h2>Your data has been uploaded</h2>
    </div>

    <div class="valid">
        <h3>It was send to the following e-mail adresses:</h3>
        <ul style="list-style-type:none;">
            <?php foreach($_['validEmails'] as $singleEmail){ ?>
            <li> 
                <?php p($singleEmail); ?> 
            </li> 
            <?php } ?>
        </ul>
    </div>

    <div class="invalid">
        <h3>No email was send to the following e-mail adresses:</h3>
        <ul style="list-style-type:none;">
            <?php foreach($_['invalidEmails'] as $singleEmail){ ?>
            <li> 
                <?php p($singleEmail); ?> 
            </li> 
            <?php } ?>
        </ul>
    </div>
</div>