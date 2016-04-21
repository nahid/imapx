<?php
/*
    This file is written with pure PHP code. so you can use it anywhere in your php project.

*/
class Imapx
{
    /*
        Here is all configurations that used in the library
    */
    private $driver = 'imap';  // here is the driver. you can use pop3 also
    private $hostname = 'imap.gmail.com'; // here is your host name,
    private $username = 'name@gmail.com'; // your server username ex: john@gmail.com
    private $password = 'your_pass';
    private $port = 993;
    private $ssl = true; // If you use false then the server ignore ssl
    private $novalidate = false; // If novalidate true then your server use own validate certificate
    /*
        end configurations
    */

    protected $isConnect = false;
    protected $stream = '';
    protected $emails = '';
    protected $inbox = [];
    protected $msgId = 0;

    protected $sortBy = [
                        'order' => [
                            'asc'     => 0,
                            'desc'    => 1,
                             ],

                        'by'    => [
                            'date'        => SORTDATE,
                            'arrival'     => SORTARRIVAL,
                            'from'        => SORTFROM,
                            'subject'     => SORTSUBJECT,
                            'size'        => SORTSIZE,
                                                ],
                        ];

    public function __construct()
    {
        $this->connect();
    }

    /*
    * connect to mail server using there credentials
    */
    public function connect($host = null, $driver = null, $user = null, $password = null, $port = null, $ssl = null, $novalidate = null)
    {
        $this->hostname = is_null($host) ? $this->hostname : $host;
        $this->driver = is_null($driver) ? $this->driver : $driver;
        $this->username = is_null($user) ? $this->username : $user;
        $this->password = is_null($password) ? $this->password : $password;
        $this->port = is_null($port) ? ':'.$this->port : ':'.$port;
        $this->ssl = is_null($ssl) ? $this->ssl : $ssl;
        $this->novalidate = is_null($novalidate) ? $this->novalidate : $novalidate;

        $this->ssl = $this->ssl ? '/ssl' : '';
        $this->novalidate = $this->novalidate ? '/novalidate-cert' : '';

        $this->stream = imap_open('{'.$this->hostname.$this->port.'/'.$this->driver.$this->ssl.$this->novalidate.'}INBOX', $this->username, $this->password) or die('Cannot connect to Server: '.imap_last_error());

        if ($this->stream) {
            $this->isConnect = true;
        }
    }

    /*
    * close the current connection
    */
    public function close()
    {
        if (!$this->isConnect) {
            return false;
        }
        imap_close($this->stream);
    }

    public function totalEmail()
    {
        if (!$this->isConnect) {
            return false;
        }

        return imap_num_msg($this->stream);
    }

    public function getInbox($page = 1, $perPage = 25, $sort = null)
    {
        if (!$this->isConnect) {
            return false;
        }

        $start = $page == 1 ? 0 : (($page * $perPage) - ($perPage - 1));
        $order = 0;
        $by = SORTDATE;

        if (is_array($sort)) {
            $order = $this->sortBy['order'][$sort[0]];
            $by = $this->sortBy['by'][$sort[1]];
        }

        $sorted = imap_sort($this->stream, $by, $order);
        $mails = array_chunk($sorted, $perPage);
        $mails = $mails[$page - 1];

        $mbox = imap_check($this->stream);
        $inbox = imap_fetch_overview($this->stream, implode($mails, ','), 0);

        if (!is_array($inbox)) {
            return false;
        }

        if (is_array($inbox)) {
            $temp_inbox = [];
            foreach ($inbox as $msg) {
                $temp_inbox[$msg->msgno] = $msg;
            }

            foreach ($mails as $msgno) {
                $this->inbox[$msgno] = $temp_inbox[$msgno];
            }
        }

        return $this->inbox;
    }

    public function readMail($id = null)
    {
        if (!$this->isConnect) {
            return false;
        }

        if (is_null($id)) {
            return false;
        }

        $this->headers = imap_headerinfo($this->stream, $id);
        $this->msgId = $id;

        return $this;
    }

    public function getDate($pattern = 'Y-m-d')
    {
        if (!$this->isConnect) {
            return false;
        }

        $date = date($pattern, strtotime($this->headers->date));

        return $date;
    }

    public function getSubject()
    {
        if (!$this->isConnect) {
            return false;
        }

        return $this->headers->subject;
    }

    public function getRecieverEmail()
    {
        if (!$this->isConnect) {
            return false;
        }

        return $this->headers->toaddress;
    }

    public function getSenderName()
    {
        if (!$this->isConnect) {
            return false;
        }

        $name = $this->headers->senderaddress;

        return $name;
    }

    public function getSenderEmail()
    {
        if (!$this->isConnect) {
            return false;
        }

        $mailboxName = $this->headers->sender[0]->mailbox;
        $host = $this->headers->sender[0]->host;

        return $mailboxName.'@'.$host;
    }

    public function getSenderLink($class = 'link')
    {
        if (!$this->isConnect) {
            return false;
        }

        $link = '<a href="mailto:'.$this->getSenderEmail().'" class="'.$class.'">'.$this->getSenderName().'</a>';

        return $link;
    }

    public function isSeen()
    {
        if (!$this->isConnect) {
            return false;
        }

        $seen = $this->headers->Unseen;

        return $seen == 'U' ? false : true;
    }

    public function isAnswered()
    {
        if (!$this->isConnect) {
            return false;
        }

        $answer = $this->headers->Answered;

        return $answer == 'A' ? true : false;
    }

    public function getSize($unit = 'kb')
    {
        if (!$this->isConnect) {
            return false;
        }

        $units = [
                'kb' => 1024,
                'mb' => 1048576,
            ];

        $size = $this->headers->Size;

        return number_format($size / $units[$unit], 2);
    }

    public function getBody($display = 'text', $decode = true)
    {
        if (!$this->isConnect) {
            return false;
        }

        $displayAs = [
            'html' => 2,
            'text' => 1,
        ];

        if (in_array($displayAs[$display], $displayAs)) {
            $display = $displayAs[$display];
        } else {
            return false;
        }

        $body = '';

        if ($decode) {
            $body = quoted_printable_decode(imap_fetchbody($this->stream, $this->msgId, $display));
        } else {
            $body = imap_fetchbody($this->stream, $this->msgId, $display);
        }

        return $body;
    }

    public function __destruct()
    {
        $this->close();
    }
}
