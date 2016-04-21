<?php
    require_once 'src/imapxPHP.php';

    $imap = new Imapx();

    $inbox = $imap->getInbox(1);

//var_dump($inbox);
    foreach ($inbox as $key => $mail) {
        if (isset($mail->subject)) {
            echo $mail->subject.'<br/>';
        }
    }
