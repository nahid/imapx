<?php namespace Nahidz\Imapx;

class Imapx
{
	private $driver;
	private $hostname;
	private $username;
	private $password;
	private $ssl;
	private $novalidate;


	protected $isConnect		= 	false;
	protected $stream			= 	'';
	protected $emails			=	'';
	protected $inbox			=	array();
	protected $msgId			=	0;

	protected $sortBy = [
                        'order' => [
                        	'asc' 	=> 0,
                            'desc' 	=> 1
                             ],

                        'by'    => [
                        	'date' 		=> SORTDATE,
                            'arrival' 	=> SORTARRIVAL,
                            'from' 		=> SORTFROM,
                            'subject' 	=> SORTSUBJECT,
                            'size'		=> SORTSIZE
                                                ]
                        ];


	function __construct()
	{
		$this->driver=config('imapx.driver');
		$this->hostname=config('imapx.host');
		$this->username=config('imapx.username');
		$this->password=config('imapx.password');
		$this->port=':'.config('imapx.port');
		$this->ssl=config('imapx.ssl')?'/ssl':'';
		$this->novalidate=config('imapx.novalidate')?'/novalidate-cert':'';



		$this->connect();
	}


	/*
	* connect to mail server using there credentials
	*/
	function connect()
	{

	$ssl = $

		$this->stream=imap_open('{'.$this->hostname.$this->port.'/'.$this->driver.$this->ssl.$this->novalidate.'}INBOX',$this->username,$this->password) or die('Cannot connect to Server: ' . imap_last_error());


		if($this->stream)
			$this->isConnect = true;
	}

	/*
	* close the current connection
	*/
	function close()
	{
		imap_close($this->stream);
	}

	public function totalEmail()
	{
		return imap_num_msg($this->stream);
	}

	public function getInbox($page=1, $perPage=25, $sort=null)
	{
		$start=$page==1?0:(($page*$perPage)-($perPage-1));
		$order=0;
		$by=SORTDATE;

		if(is_array($sort)){
			$order	= $this->sortBy['order'][$sort[0]];
			$by	= $this->sortBy['by'][$sort[1]];
		}

		$sorted=imap_sort($this->stream, $by, $order);
		$mails = array_chunk($sorted, $perPage);
		$mails = $mails[$page-1];

		$mbox = imap_check($this->stream);
		$inbox = imap_fetch_overview($this->stream, implode($mails,','), 0);

		if(!is_array($inbox)) return false;

		if(is_array($inbox)){
			$temp_inbox=array();
			foreach($inbox as $msg){
				$temp_inbox[$msg->msgno]=$msg;
			}

			foreach($mails as $msgno){
				$this->inbox[$msgno]=$temp_inbox[$msgno];
			}
		}

		return $this->inbox;
	}


	function readMail($id=null)
	{
		if(is_null($id)) return false;

		$this->headers=imap_headerinfo($this->stream, $id);
		$this->msgId=$id;

		return $this;

	}

	function getDate($pattern='Y-m-d')
	{
		$date =date($pattern, strtotime($this->headers->date));
		return $date;
	}

	function getSubject()
	{
		return $this->headers->subject;

	}

	function getRecieverEmail()
	{
		return $this->headers->toaddress;
	}

	function getSenderName()
	{
		$name = $this->headers->senderaddress;
		return $name;
	}

	function getSenderEmail()
	{
		$mailboxName = $this->headers->sender[0]->mailbox;
		$host 	=	$this->headers->sender[0]->host;

		return $mailboxName.'@'.$host;
	}

	function isSeen()
	{
		$seen=$this->headers->Unseen;

		return $seen=='U'?false:true;
	}

	function isAnswered()
	{
		$answer = $this->headers->Answered;
		return $answer=='A'?true:false;
	}

	function getSize($unit='kb')
	{
		$units=[
				'kb' => 1024,
				'mb' => 1048576
			];

		$size = $this->headers->Size;
		return number_format($size/$units[$unit], 2);
	}

	function getBody()
	{
		$body = imap_fetchbody($this->stream, $this->msgId, 2);
		return $body;
	}

	function __destruct()
	{
		$this->close();
	}

}
