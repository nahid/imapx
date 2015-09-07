<?php
    require_once "src/ImapxPHP.php";

    $imap=new Imapx;

    $mail=$imap->getInbox();

    var_dump($mail);
 ?>
