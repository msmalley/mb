<?php

class mongobase_auth extends mongobase_mb
{

	private static $verified_salts = false;
	public static $logged_in = false;

	protected static $options;
	protected static $env;

	private function plain_id($id = false, $pw_key = 'p')
	{
		$db = mb_class('db');
		$results = $db->find(array('col'=>'users'));
		$user_salt = $this->get_user_salt();
		if(is_array($results)){
			foreach($results as $result){
				$uid = $db->_id($result['_id']);
				$key = hash('sha256',substr($result[$pw_key],0,3));
				if(hash('sha256',$uid.$user_salt.$key) == $id) return $uid;
			}
		}
	}

	private function salts()
	{
		$config_folder = dirname($this::$config);
		$security = $config_folder.'/security.ini';
		if(file_exists($security))
		{
			$nonces = parse_ini_file($security);
			if(is_array($nonces) && count($nonces) > 0)
			{
				foreach($nonces as $nonce_name => $nonce)
				{
					self::$verified_salts->$nonce_name = $nonce;
				}
			}
		}
	}

	function __construct($options = array(), $key = 'auth')
	{
		$defaults = array(
			'key' => $key
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		// REGISTER MODULE
		parent::__construct($settings, $settings['key']);

		$this::$verified_salts = new stdClass();

		$this->salts();
	}

	public function tokenize($token = false, $key = 'annonymous', $slug_key = false)
	{
		$progress['success'] = false;
		$progress['message'] = _('Did not pass token check!');
		if($token)
		{
			$now = date('g A', strtotime('today'));
			if($this::$env->slug === $key)
			{
				if(hash('sha256', $slug_key.'_'.$now.'_'.parent::$env->urls->root) == $token)
				{
					$token = hash('sha256', $key.'_'.$now.'_'.parent::$env->urls->root);
				}
			}
			$mb = mb_class();
			if(hash('sha256', $this::$env->slug.'_'.$now.'_'.parent::$env->urls->root) === $token)
			{
				$progress = true;
			}
		}
		return $progress;
	}

	public function token_check($token = false, $refer = false, $other_root = false, $other_slug = false)
	{
		$time = date('g A', strtotime('today'));
		$hash = hash('sha256', $time);

		$root_url = $this::$env->urls->root;
		$slug = $this::$env->slug;

		if($root_url.$slug == $refer)
		{
			if($other_root) $root_url = $other_root;
			if($other_slug) $slug = $other_slug;
			$hash = hash('sha256', $slug.'_'.$time.'_'.$root_url);
		}
		if($hash == $token) return true;
		else return false;
	}

	public function detokenize($token = false, $key = 'default')
	{
		$progress['success'] = false;
		$progress['message'] = _('Did not pass nonce check!');
		if($token)
		{
			if($this->is_logged_in())
			{
				// Check both copies of ID
				if(parent::$uid && (parent::$uid == $this->get_id(true)))
				{
					$uid = parent::$uid;
					$nonce_salt = $this->get_nonce_salt();
					if(hash('sha256', $nonce_salt.$uid.$key) == $token)
					{
						$progress = true;
					}
				}
			}
		}
		return $progress;
	}

	public function nonce($key = 'default')
	{
		$nonce = false;
		if($this->is_logged_in())
		{
			// Check both copies of ID
			if(parent::$uid && (parent::$uid == $this->get_id(true)))
			{
				$uid = parent::$uid;
				$nonce_salt = $this->get_nonce_salt();
				$nonce = hash('sha256', $nonce_salt.$uid.$key);
			}
		}
		return $nonce;
	}

	public function get_id($get = false, $other_id = false)
	{
		$id = false;
        if($other_id) return $this->plain_id($other_id);
        else {
            $cookie_name = $this->cookie_name();
            if(isset($_COOKIE[$cookie_name])){
                //setcookie($cookie_name, false, -1, '/');
                $id = $_COOKIE[$cookie_name];
                if($get) return $this->plain_id($id);
            }else{
                $id = false;
            }
        }
        return $id;
	}

	public function log_in($username = false, $password = false, $collection = 'users', $username_key = 'un', $pw_key = 'p')
	{
		if(!$username || !$password)
		{
			return false;
		};

		// Incude classes
		$db = mb_class('db');
		$auth = mb_class('auth');

		// Get salts
		$user_salt = $auth->get_user_salt();
		$site_salt = $auth->get_site_salt();
		$cookie_salt = $auth->get_cookie_salt();
		$cookie_name = $auth->cookie_name();

		// Construct query
		$query = array(
			'col'	=> $collection,
			'where'	=> array(
				$username_key => $username,
			)
		);
		$results = $db->find($query);

		// Set defaults
		$pass = $results[0][$pw_key];
		$key = false;

		// Run array
		if(is_array($results)){

			// Check passwords match
			$pass_check = hash('sha256', $site_salt.$password.$cookie_salt.$username);

			if($pass==$pass_check){
				$id = $db->_id($results[0]['_id']);
				$key = hash('sha256',substr($results[0][$pw_key],0,3));

				// Set time
				$last = 0;
				$ttl = (int) mb_option('mb', 'ttl');
				if(!$ttl) $ttl = 3600; // ONE HOUR

				// Fill cookie
				$last = time()+$ttl; // CUSTOMISED
				setcookie($cookie_name, hash('sha256',$id.$user_salt.$key), $last, '/'); // Expires in one hour
				$this::$logged_in = true;
			}
		}
	}

	public function cookie_name()
	{
		$cookie_salt = $this->get_cookie_salt();
		$site_salt = $this->get_site_salt();
		$user_salt = $this->get_user_salt();
		$cookie_name = hash('sha256',$cookie_salt.$site_salt.$user_salt);
		return $cookie_name;
	}

	public function get_cookie_salt()
	{
		if(isset(self::$verified_salts) && isset(self::$verified_salts->cookie))
		{
			$cookie_salt = hash('sha256',self::$verified_salts->cookie);
		}
		else $cookie_salt = hash('sha256','need_to_change_'.parent::$env->urls->root);
		return $cookie_salt;
	}

	public function get_site_salt()
	{
		if(isset(self::$verified_salts) && isset(self::$verified_salts->site))
		{
			$site_salt = hash('sha256',self::$verified_salts->site);
		}
		else $site_salt = hash('sha256',$this::$id);
		return $site_salt;
	}

	public function get_nonce_salt()
	{
		if(isset(self::$verified_salts) && isset(self::$verified_salts->nonce))
		{
			$nonce_salt = hash('sha256',self::$verified_salts->nonce);
		}
		else $nonce_salt = hash('sha256',date('l jS \of F Y'));
		return $nonce_salt;
	}

	public function get_user_salt()
	{
		if(isset(self::$verified_salts) && isset(self::$verified_salts->user))
		{
			$user_salt = hash('sha256',self::$verified_salts->user);
		}
		else $user_salt = hash('sha256','need_to_change_'.parent::$env->paths->root);
		return $user_salt;
	}

	public function get_rand_salt()
	{
		if(isset(self::$verified_salts) && isset(self::$verified_salts->rand))
		{
			$rand_salt = hash('sha256',self::$verified_salts->rand);
		}
		else $rand_salt = hash('sha256','need_to_change_'.mt_rand(0, 999));
		return $rand_salt;
	}

	public function is_logged_in($and_is_admin = false, $pw_key = 'p')
	{
		$cookie_name = $this->cookie_name();
		$user_salt = $this->get_user_salt();
		$cookie_salt = $this->get_cookie_salt();
		$logged_in = false;
		if(isset($_COOKIE[$cookie_name])){
			$user_id = $_COOKIE[$cookie_name];
			/* HACK FOR CHECKING SALTY COOKIES */
			$db = mb_class('db');
			$query = array(
				'col'	=> 'users'
			);
			$results = $db->find($query);
			$the_result = false; $key = false;
			if(is_array($results)){
				foreach($results as $user){
					if(isset($user[$pw_key])){
						if($logged_in !== true)
						{
							$key = hash('sha256',substr($user[$pw_key],0,3));
							$userid = $db->_id($user['_id']);
							if(hash('sha256',$userid.$user_salt.$key)==$user_id){
								parent::$uid = $userid;
								$this->active_user = $user;
								if($and_is_admin){
									if(isset($user['acl'])&&$user['acl']>=10){
										$logged_in = true;
									}
								}else{
									$logged_in = true;
								}
							}
						}
					}
				}
			}
		};
		$mb = mb_class('mb');
		$logged_in = $mb->apply_filters('auth_is_loggedin', $logged_in);

		if($logged_in)
		{
			// Set time
			$last = 0;
			$ttl = (int) mb_option('mb', 'ttl');
			if(!$ttl) $ttl = 3600; // ONE HOUR

			// Fill cookie
			$last = time()+$ttl; // CUSTOMISED
			setcookie($cookie_name, hash('sha256',$userid.$user_salt.$key), $last, '/'); // Expires in one hour
		}

		$this::$logged_in = $logged_in;
		return $logged_in;
	}
}