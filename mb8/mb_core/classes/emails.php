<?php

class mongobase_emails extends mongobase_mb
{

	private static $key;
	protected static $options;
	protected static $env;

	protected function option($key = 'key', $default = false)
	{
		$class = $this::$key;
		$options = $this::$options;
		if(isset($options[$key])) return $options[$key];
		else return $default;
	}

	function __construct($options = array(), $key = 'emails')
	{
		$this::$key = $key;
		$defaults = array(
			'key'		=> $key,
			'vendor'	=> 'class.phpmailer',
			'class'		=> 'PHPMailer'
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		// REGISTER MODULE
		parent::__construct($settings, $settings['key']);
		
		// Load Vendors Libs
		if(file_exists(dirname(__FILE__).'/vendors/'.$settings['vendor'].'.php')){
			require_once(dirname(__FILE__).'/vendors/'.$settings['vendor'].'.php');
		}
	}

	public function send($args = array())
	{
		$defaults = array(
			'to'		=> false,
			'to_name'	=> false,
			'subject'	=> false,
			'message'	=> false,
			'headers'	=> null,
			'cc'		=> null,
			'bcc'		=> null,
			'rpath'		=> null
		);
		$settings = array_merge($defaults, $args);
		$progress['success'] = false;
		$progress['message'] = $this->__('Unable to send message');
		if(!$settings['to'] || !$settings['subject'] || !$settings['message']) return $progress;
		$class_name = mb_option('emails','class');
		if(!class_exists($class_name)) return $progress;
		$mail = new $class_name();
		$mail->IsSMTP();
		if(isset($settings['bcc'])) $mail->addBCC($settings['bcc'], $settings['bcc']);
		try {
			$host		= $this->option('smtphost');
			$port		= $this->option('smtpport');
			$username	= $this->option('username');
			$password	= $this->option('password');
			$email		= $this->option('useremail');
			$name		= $this->option('name');
			$auth		= $this->option('auth', true);
			if($auth == 'false') $auth = false;
			//$mail->SMTPDebug  = 2;
			$mail->SMTPAuth   = $auth;
			$mail->Host       = $host;
			$mail->Port       = $port;
			$mail->Username   = $username;
			$mail->Password   = $password;
			if(isset($email)) $mail->AddReplyTo($email, $name);
			else $mail->AddReplyTo($username, $name);
			$plain_to = $settings['to'];
			if(is_array($settings['to'])){
				$plain_to = ''; $i = 0;
				foreach($settings['to'] as $to){
					if($i<1) $plain_to = $to[0];
					else $plain_to = $plain_to.', '.$to[0];
					$mail->AddAddress($to[0], $to[1]);
					$i++;
				}
			}else{
				$mail->AddAddress($settings['to'], $settings['to_name']);
			}
			if(isset($email)) $mail->SetFrom($email, $name);
			else $mail->SetFrom($username, $name);
			if(isset($email)) $mail->AddReplyTo($email, $name);
			else $mail->AddReplyTo($username, $name);
			$mail->Subject = $settings['subject'];
			$plain_body = $this->__('To view the message, please use an HTML compatible email viewer!');
			if(function_exists('html2text')) $plain_body = @html2text($settings['message']);
			$mail->AltBody = $plain_body;
			$mail->MsgHTML($settings['message']);
			//$mail->AddAttachment('images/phpmailer.gif');      // attachment
			//$mail->AddAttachment('images/phpmailer_mini.gif'); // attachment
			$mail->Send();
			$progress['success'] = true;
			$progress['message'] = $this->__('Succesfully sent message');
			/* TODO- REPLACE IMAP FUNCTIONALITY THAT WAS REMOVED */
		} catch (phpmailerException $e) {
			$progress['message'] = $e->errorMessage();
		} catch (Exception $e) {
			$progress['message'] = $e->getMessage();
		}
		return $progress;
	}

	public function contact($options = array(), $nonce = false, $echo_as_json = false)
	{
		$progress = array(
			'success'	=> false,
			'message'	=> _('Unable to send invite')
		);
		
		$passed = true;
		$auth = mb_class('auth');
		$security = $auth->detokenize($nonce);
		if(!$nonce) $security = false;
		if(is_array($security))
		{
			$passed = false;
		}

		if($passed === true)
		{
			$app_name = false;
			$defaults = array(
				'subject'	=> 'Message via '.$app_name,
				'message'	=> false,
				'from_id'	=> false,
				'to_id'		=> false
			);
			$settings = array_merge($defaults, $options);
			if($settings['from_id'] && $settings['to_id'])
			{
				$db = mb_class('db');
				$from = $db->find(array(
					'col'	=> 'users',
					'id'	=> $settings['from_id']
				));
				if(is_array($from))
				{
					$to = $db->find(array(
						'col'	=> 'users',
						'id'	=> $settings['to_id']
					));
					if(is_array($to))
					{
						$to_name = false;
						$to_email = false;
						$from_name = false;
						$from_email = false;
						if(isset($from['fn']) && isset($from['ln']) && isset($from['e']))
						{
							$from_name = $from['fn'].' '.$from['ln'];
							$from_email = $from['e'];
						}
						if(isset($to['fn']) && isset($to['ln']) && isset($to['e']))
						{
							$to_name = $to['fn'].' '.$to['ln'];
							$to_email = $to['e'];
						}
						if($from_name && $from_email && $to_name && $to_email)
						{
							$message = array(
								'to'		=> $to_email,
								'to_name'	=> $to_name,
								'subject'	=> $settings['subject'],
								'message'	=> $settings['message'].'<p>------------</p><p>This message was sent from '.$from_name.' at '.$from_email
							);
							$emails = mb_class('emails');
							$progress = $emails->send($message);
						}
					}
				}
			}
		}
		if($echo_as_json === false) return $progress;
		else echo json_encode($progress);
		exit;
	}
	
	public function invite($options = array(), $nonce = false)
	{
		$progress = array(
			'success'	=> false,
			'message'	=> _('Unable to send invite')
		);

		$passed = true;
		$auth = mb_class('auth');
		$security = $auth->detokenize($nonce);
		if(!$nonce) $security = false;
		if(is_array($security))
		{
			$passed = false;
		}

		if($passed === true)
		{

			$defaults = array(
				'emails'	=> 'users',
				'col'		=> 'signups',
				'action'	=> false,
				'data'		=> false,
				'unique'	=> true,
				'email'		=> false,
				'name'		=> false,
				'key'		=> false
			);
			$settings = array_merge($defaults, $options);
			if($settings['email'])
			{
				$db = mb_class('db');
				$check = array(
					'col'	=> $settings['emails'],
					'where'	=> array(
						'e'		=> $settings['email']
					)
				);
				$email = $db->find($check);
				if(is_array($email) && isset($email[0]['_id']))
				{
					$progress['message'] = _('Email already registered');
				}
				else
				{
					$prevent_invite = false;
					$auth = mb_class('auth');
					$user_salt = $auth->get_user_salt();

					// This allows us to add an optional invite code to invite form
					if($settings['key'] && count($settings['key']) == 4)
					{
						$code_check = substr(hash('sha256', $settings['email'].$user_salt), 0, 4);
						if($code_check != $settings['key'])
						{
							$prevent_invite = true;
						}
					}
					if($prevent_invite == true)
					{
						$progress['message'] = _('Incorrect Invite Code');
					}
					else
					{
						if($settings['unique'] === 'true' || $settings['unique'] === true)
						{
							$check_invites = array(
								'col'	=> $settings['col'],
								'where'	=> array(
									'e'		=> $settings['email']
								)
							);
							$invites = $db->find($check_invites);
							if(is_array($invites) && isset($invites[0]['_id']))
							{
								$progress['message'] = _('Email Address Sent Invite Already');
							}
							else
							{
								$settings['unique'] = false;
							}
						}

						if($settings['unique'] === 'false' || $settings['unique'] === false)
						{
							if($settings['action'] != 'reinvite')
							{
								$object = array(
									'e'	=> $settings['email']
								);
								if(isset($settings['data']) && is_array($settings['data']))
								{
									foreach($settings['data'] as $key => $value)
									{
										if($key == 'acl') $value = (int) $value;
										$object[$key] = $value;
									}
								}
								$signup = array(
									'col'	=> $settings['col'],
									'obj'	=> $object
								);
								$added = $db->mbsert($signup);
							}
							else
							{
								$check_invites = array(
									'col'	=> $settings['col'],
									'where'	=> array(
										'e'		=> $settings['email']
									)
								);
								$invites = $db->find($check_invites);
								if(is_array($invites) && isset($invites[0]['_id']))
								{
									$added = $db->_id($invites[0]['_id']);
								}
							}
							if($added)
							{
								$emails = mb_class('emails');
								$progress['results'] = $added;

								$hash = hash('sha256', $settings['email'].$user_salt.$added);

								$intro = false;
								$to_name = $settings['email'];
								if($settings['name'])
								{
									$to_name = $settings['name'];
									$intro = 'Dear '.$settings['name'];
								}

								$title = $this::$options['mb']['title'];
								$url = $this::$env->urls->root.'?invite=mbi'.$hash;
								$link = '<a href="'.$url.'">'.$url.'</a>';
								$team = 'Team '.$title;
								$home = '<a href="'.$this::$env->urls->root.'">'.$this::$env->urls->root.'</a>';

								$message_options = array(
									'to'		=> $settings['email'],
									'to_name'	=> $to_name,
									'subject'	=> $title.' :: Verify Account',
									'message'	=> $intro.'
										<p>Thank you for joining '.$title.'</p>
										<p>Please verify your email by clicking on the following link - '.$link.'</p>
										<p>Kind regards;</p>
										<p><strong>'.$team.'</strong><br />'.$home.'</p>'
								);
								$message = $emails->send($message_options);
								if($message['success'] == true)
								{
									$progress['success'] = true;
									$progress['message'] = _('Successfully sent invite');
								}
								else
								{
									$progress['message'] = _('Email added to database but unable to send invite - please try again');
								}
							}
							else
							{
								$progress['message'] = _('Unable to Add Invite to DB');
							}
						}
						else
						{
							$check_invites = array(
								'col'	=> $settings['col'],
								'where'	=> array(
									'e'		=> $settings['email']
								)
							);
							$invites = $db->find($check_invites);
							if(is_array($invites) && isset($invites[0]['_id']))
							{
								$added = $db->_id($invites[0]['_id']);
							}
							if($added)
							{
								$emails = mb_class('emails');

								$hash = hash('sha256', $settings['email'].$user_salt.$added);

								$intro = false;
								$to_name = $settings['email'];
								if($settings['name'])
								{
									$to_name = $settings['name'];
									$intro = 'Dear '.$settings['name'];
								}

								$title = $this::$options['mb']['title'];
								$url = $this::$env->urls->root.'?invite=mbi'.$hash;
								$link = '<a href="'.$url.'">'.$url.'</a>';
								$team = 'Team '.$title;
								$home = '<a href="'.$this::$env->urls->root.'">'.$this::$env->urls->root.'</a>';

								$message_options = array(
									'to'		=> $settings['email'],
									'to_name'	=> $to_name,
									'subject'	=> $title.' :: Verify Account',
									'message'	=> $intro.'
										<p>Thank you for joining '.$title.'</p>
										<p>Please verify your email by clicking on the following link - '.$link.'</p>
										<p>Kind regards;</p>
										<p><strong>'.$team.'</strong><br />'.$home.'</p>'
								);
								$message = $emails->send($message_options);
								if($message['success'] == true)
								{
									$progress['success'] = true;
									$progress['message'] = _('Successfully sent invite');
								}
								else
								{
									$progress['message'] = _('Email added to database but unable to send invite - please try again');
								}
							}
							else
							{
								$progress['message'] = _('Unable to locate hash key');
							}
						}
					}
				}
			}
			else
			{
				$progress['message'] = _('Email address required');
			}
		}
		else
		{
			$progress['message'] = _('Did not pass nonce check');
		}
		return $progress;
	}

}