<?php
    require_once "src/imapxPHP.php";

    $imap=new Imapx;

    $msg=$imap->readMail(7);
    echo $msg->getSenderLink();
 ?>
