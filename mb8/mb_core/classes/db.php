<?php

class mongobase_db extends mongobase_mb
{

	public static $db = null;
	public static $m = null;

	protected static $env;
	protected static $options;

	private function connect()
	{
		if(self::$db) return self::$db;
		$options = $this->get_option('db',array(
			'username'	=> false,
			'password'	=> false,
			'name'		=> $this::$id,
			'host'		=> 'localhost',
			'port'		=> '27017'
		));
		try{
			if($options['username'] && $options['password']) $mongo = new Mongo("mongodb://".$options['username'].":".$options['password']."@".$options['host'].":".$options['port']."/".$options['name']);
			else $mongo = new Mongo("mongodb://".$options['host'].":".$options['port']);
			self::$m = $mongo;
			//if($username && $password) self::$db = $mongo;
			//else self::$db = $mongo->$db_name;
			self::$db = $mongo->$options['name'];
			return true;
		}catch(MongoConnectionException $e){
			$this->error($this->__('Error connecting to MongoDB server'));
		}catch(MongoException $e) {
			$this->error($this->__('Error: mongoDB error').$e->getMessage());
		}catch(MongoCursorException $e) {
			$this->error($this->__('Error: probably username password in config').$e->getMessage());
		}catch(Exception $e) {
			$this->error($this->__('Error: ').$e->getMessage());
		}; return false;
	}

	public function disconnect()
	{
		if(isset($this::$m)) $this::$m->close();
	}

	private function get_db_collections()
	{
		if(!self::$db) $this->connect();
		$db = self::$db;
		$cols = false;
		$total_media = $db->fs->files->count();
		$collections['signups'] = array('signups'=>0);
		$collections['users'] = array('users'=>0);
		$collections['content'] = array('content'=>0);
		$collections['emails'] = array('emails'=>0);
		$collections['trash'] = array('trash'=>0);
		foreach($collections as $collection){
			foreach($collection as $key => $base_count){
				$count = $this->count(array('col'=>$key))+$base_count;
				/* TODO: REMOVE TEMP HACK WHILST WAITING TO SWITCH NAMES */
				if($key=='signups') $key = $this->__('Sign-Ups');
				$cols[] = array(
					'title' => $key,
					'value'	=> $count
				);
			}
		}
		$cols[] = array(
			'title'	=> $this->__('Media'),
			'value'	=> $total_media
		);
		$db_stats = $db->command(array('dbStats'=>1));
		$cols[] = array(
			'title'	=> $this->__('DB Indexes'),
			'value'	=> $db_stats['indexes']
		);
		$cols[] = array(
			'title'	=> $this->__('DB Collections'),
			'value'	=> $db_stats['collections']
		);
		$cols[] = array(
			'title'	=> $this->__('DB Objects'),
			'value'	=> $db_stats['objects']
		);
		return $cols;
	}

	private function export($args = array()){
		$defaults = array(
			'col'		=> 'mbsert',
			'type'		=> 'dump',
			'name'		=> false,
			'format'	=> 'json',
			'limit'		=> 0,
			'where'		=> array()
		);
		$settings = array_merge($defaults, $args);
		if(!$settings['name']) $settings['name'] = time().'_export';
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
            $find_this = array(
                'col'   => $settings['col']
            ); $results = $this->find($find_this);
			$content = json_encode($results);
			$content = $this->apply_filters('export_results', $content);
            $today = strtotime('today');
			$file = $settings['name'].'.'.$settings['format'];
			file_put_contents(dirname(dirname(dirname(__FILE__))).'/mb_app/export/'.$today.'_'.$file, $content);
			return $this->__('Successfully Exported: '.$file);
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			return $this->__('Error: ').$e->getMessage();
		}
	}

	function __construct($options = array(), $key = 'db')
	{
		$defaults = array(
			'key' => $key
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		// REGISTER MODULE
		parent::__construct($settings, $settings['key']);

		// Check if needed
		$is_needed = false;
		if(isset($this::$ini['db'])) $is_needed = true;

		if($is_needed)
		{
			$this->requirements(array(
				'mongodb'	=> 2,
				'mphp'		=> 1.2
			));
			$this->connect();
		}
		else
		{
			$this->pretty_print(_('Service Unavailable'), _('Database Configuration / Activation Required'));
		}
	}

	public function mid($id = false, $return_as_string = false)
	{
		 $mid = new MongoID($id);
		 if($return_as_string) return (string) $mid;
		 else return $mid;
	}

	public function arrayed($these_objs = false)
	{
		if(is_object($these_objs)){
			$objects = array();
			foreach($these_objs as $this_obj) {
				$this_object = array();
				foreach($this_obj as $key => $value){
					$this_object[$key] = $value;
				} $objects[] = $this_object;
			}
			if(is_array($objects)){
				if(!empty($objects)){
					return $objects;
				}
			}
		}
	}

	public function get_user($args = array()){
		$defaults = array(
			'col'		=> 'users',
			'id'		=> null
		); $settings = array_merge($defaults, $args);
		if(!isset($settings['id']) || !$settings['id']) {
			$auth = mb_class('auth');
			$settings['id'] = $auth->get_id(true);
		};
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			if(is_object($db) && $settings['id'] && $settings['col']){
				$collection = $db->$settings['col'];
				$user = $collection->findOne(array('_id' => new MongoId($settings['id'])));
				if(is_array($user)){
					$washed_user = false;
					foreach($user as $attribute => $value){
						if($attribute != '_id'){
							if($attribute != 'pass'){
								$washed_user[$attribute] = $value;
							}else{
								$washed_user['key'] = hash('sha256',substr($value,0,3));
							}
						}else{
							$washed_user['id'] = $this->_id($value);
						}
					}; return $washed_user;
				}
			}else{
				if(!$settings['id'] || !$settings['col']){
					// DO NOTHING - INCLUDING NO ERROR
				}else{
					$this->error($this->__('MongoDB Not Initiated'));
				}
			}
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			$this->error($this->__('Error: ').$e->getMessage());
		}
	}

	public function count($args = array())
	{
		$defaults = array(
			'col'	=> 'mbsert',
			'where' => array()
		);
		$settings = array_merge($defaults, $args);
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			if(is_object($db)){
				$collection = $db->$settings['col'];
				$results = $collection->find($settings['where'])->count();
				return $results;
			}else{
				$this->error($this->__('MongoDB Not Initiated'));
			}
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			$this->error($this->__('Error: ').$e->getMessage());
		}
	}

	public function aggregate($args = array())
	{
		$defaults = array(
			'col'	=> 'mbsert',
			'pipe'	=> array(),
			'op'	=> array()
		);
		$settings = array_merge($defaults, $args);
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			if(is_object($db)){
				$collection = $db->$settings['col'];
				$results = $collection->aggregate($pipe, $op);
				return $results;
			}else{
				$this->error($this->__('MongoDB Not Initiated'));
			}
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			$this->error($this->__('Error: ').$e->getMessage());
		}
	}

	public function archive($options = array(), $archive_col = 'archive')
	{
		$defaults = array(
			'col'	=> 'mbsert',
			'id'	=> false,
			'where'	=> false
		);
		$vars = array_merge($defaults, $options);
		$progress['success'] = false;
		$progress['message'] = _('Unable to archive');

		$got_where = false;
		$objects = false;
		$object = $this->find($vars);
		if(is_array($vars['where']))
		{
			$objects = $this->find($vars);
			$got_where = true;
		}
		else
		{
			$object = $this->find($vars);
			$objects[] = $object;
		}

		if(is_array($objects))
		{
			foreach($objects as $object)
			{
				if(is_array($object) && isset($object['_id']))
				{
					$id = $this->_id($object['_id']);
					$object['__id'] = $id;
					$object['__col'] = $vars['col'];
					unset($object['_id']);

					$archived = $this->mbsert(array(
						'col'	=> $archive_col,
						'obj'	=> $object
					));

					if($got_where)
					{
						if(isset($vars['id']) && !$vars['id'])
						{
							$vars['id'] = $id;
						}
					}

					if($archived)
					{
						$results = $this->delete($vars);
						if($results)
						{
							if(is_array($vars['where']))
							{
								$this_progress['success'] = true;
								$this_progress['results'] = $results;
								$this_progress['message'] = _('Archived Successfully');
								$progress['archives'][] = $this_progress;
							}
							else
							{
								$progress['success'] = true;
								$progress['results'] = $results;
								$progress['message'] = _('Archived Successfully');
							}

						}
					}
				}
			}
		}
		return $progress;
	}

	public function delete($args = false, $filter = true)
	{
		$defaults = array(
			'col'		=> 'mbsert',
			'id'		=> false
		);
		$settings = array_merge($defaults, $args);
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			$collection = $db->$settings['col'];
			$criteria = array(
				'_id' => new MongoId($settings['id']),
			);
			$progress = $collection->remove($criteria,array('safe'=>true));
			if($filter) $progress = $this->apply_filters('mb_db_delete_after', $progress, $settings);
			return $progress['n'];
		}catch(Exception $e){
			return $this->__('Error: ').get_class($e).' : '.$e->getMessage();
		}
	}

	public function unique($col=false, $query=false)
	{
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			if(is_object($db)){
				$collection = $db->$col;
				$results = $collection->find($query)->count();
				if($results>0) return false;
				else return true;
			}else{
				$this->error($this->__('MongoDB Not Initiated'));
			}
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			$this->error($this->__('Error: ').$e->getMessage());
		}
	}
	public function distincts($col=false, $field=false)
	{
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			if(is_object($db)){
				$collection = $db->$col;
				$results = $db->command(array("distinct"=>$col,"key"=>$field));
				return $results['values'];
			}else{
				$this->error($this->__('MongoDB Not Initiated'));
			}
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			$this->error($this->__('Error: ').$e->getMessage());
		}
	}

	public function _id($id = null)
	{
        $mongo_id = '';
        if (isset($id)) {
            if(is_object($id)){
                foreach($id as $key => $value){
                    if($key=='$id'){
                        $mongo_id = $value;
                    }
                }
            }
            return (string)$mongo_id;
        } else {
            return (string)$id;
        }
	}

	public function get_field($col, $id, $field)
	{
		$query = array(
			'col'	=> $col,
			'id'	=> $id
		); $result = $this->find($query);
		if($result){
			foreach($result as $key => $value){
				if($key==$field) return $value;
			}
		}
	}

	public function update($args = false, $filter = true)
	{
		$defaults = array(
			'col'		=> 'mbsert',
			'obj'		=> false,
			'register'	=> true,
			'override'	=> false,
			'id'		=> false
		);
		$settings = array_merge($defaults, $args);
		try{

			if($settings['id']){
				$search_for_id = array(
					'col'	=> $settings['col'],
					'id'	=> $settings['id']
				); $original_object = $this->find($search_for_id);
				if(is_array($original_object)){

					$temp_obj = false;
					foreach($original_object as $attribute => $value){
						if($attribute != '_id') $temp_obj[$attribute] = $value;
					};

					if(is_array($settings['obj'])){

						foreach($settings['obj'] as $new_attr => $new_value){
							if(is_array($new_value) && isset($temp_obj[$new_attr]) && is_array($temp_obj[$new_attr])){
								if($settings['override']===true){
									$temp_obj[$new_attr] = $new_value;
								}else{
									$merged_value = array_merge($temp_obj[$new_attr], $new_value);
									$temp_obj[$new_attr] = $merged_value;
								}
							}else{
								$temp_obj[$new_attr] = $new_value;
							}
						}

						$data = $this->apply_filters('mb_db_update_before', $temp_obj, $settings);

						$update_obj = array(
							'col'		=> $settings['col'],
							'obj'		=> $data,
							'id'		=> $settings['id'],
							'register'	=> $settings['register']
						);
						$updated_id = $this->mbsert($update_obj);
						if($filter) $updated_id = $this->apply_filters('mb_db_update_after', $updated_id, $settings);
						return $updated_id;
					}else{
						return false;
					}

				}else{
					return false;
				}
			}else{
				return false;
			}

		}catch(Exception $e){
			// should be able to do this class wide on the base object
			return $this->__('Error: ').$e->getMessage();
		}
	}

	public function mbsert($args = false, $filter = true)
	{
		$defaults = array(
			'col'		=> 'mbsert',
			'stamped'	=> false,
			'register'	=> true,
			'string_id'	=> false,
			'obj'		=> false,
			'id'		=> NULL,
			'ts'		=> false
		);
		$settings = array_merge($defaults, $args);
		if(!self::$db) $this->connect();
		$db = self::$db;
		try{
			$collection = $db->$settings['col'];
			$mongo_id = new MongoID($settings['id']);
			$key = array("_id"=>$mongo_id);
			$data = $settings['obj'];

			$data = $this->apply_filters('mb_db_mbsert_before', $data, $settings);

			if($settings['string_id']) $key = array("_id"=>$settings['id']);
			if($settings['stamped'] || $settings['ts']) $data['ts'] = new MongoDate;

			$results = $db->command( array(
				'findAndModify' => $settings['col'],
				'query' => $key,
				'update' => $data,
				'new' => true,
				'upsert' => true,
				'fields' => array( '_id' => 1 ) // mongoDB returns these values
			) );
			if($settings['string_id']) {
				if(isset($results['value']) && isset($results['value']['_id'])){
					$id = $results['value']['_id'];
				}else{
					$id = false;
				};
				if($filter) $id = $this->apply_filters('mb_db_mbsert_after', $id, $settings);
				return $id;
			}
			if(isset($results['value']))
			{
				$id = $this->_id($results['value']['_id']);
				if($filter) $id = $this->apply_filters('mb_db_mbsert_after', $id, $settings);
				return $id;
			}
			else return false;
		}catch(Exception $e){
			// should be able to do this class wide on the base object
			return $this->__('Error: ').$e->getMessage();
		}
	}

	public function get_dashboard()
	{
		$data = $this->apply_filters('db_get_default_dashboard_data', false, $this);
		$data['collections']['list'] = $this->get_db_collections();
		$data['system']['list'] = $this->get_system_settings();
		return $data;
	}

	public function get_system_settings()
	{
		if(!self::$db) $this->connect();
		$db = self::$db;
		$m = self::$m;
		$adminDB = $m->admin; //require admin priviledge
		$mongodb_info = $adminDB->command(array('buildinfo'=>true));
		$mongodb_version = (float)$mongodb_info['version'];
		$php_version = floatval(PHP_VERSION);
		$php_driver_version = MONGO::VERSION;
		$settings[] = array(
			'key'	=> 'mongodb',
			'title'	=> $this->__('MongoDB Version'),
			'value'	=> $mongodb_version
		);
		$settings[] = array(
			'key'	=> 'mongophp',
			'title'	=> $this->__('MongoDB PHP Drivers'),
			'value'	=> $php_driver_version
		);
		$settings[] = array(
			'key'	=> 'mongobase',
			'title'	=> $this->__('MongoBase Version'),
			'value'	=> $this::$versions['mb']
		);
		$settings[] = array(
			'key'	=> 'jquery',
			'title'	=> $this->__('jQuery Version'),
			'value'	=> $this::$versions['jquery']
		);
		$settings[] = array(
			'key'	=> 'php',
			'title'	=> $this->__('PHP Version'),
			'value'	=> $php_version
		);
		$db_stats = $db->command(array('dbStats'=>1));
		$data_size_kb = round($db_stats['dataSize']/1024);
		$index_size_kb = round($db_stats['indexSize']/1024);
		$storage_size_mb = round($db_stats['storageSize']/(1024*1024), 2);
		$total_size_mb = round($db_stats['fileSize']/(1024*1024), 2);
		$settings[] = array(
			'title'	=> $this->__('Data Size'),
			'value'	=> $data_size_kb.' KB'
		);
		$settings[] = array(
			'title'	=> $this->__('Index Size'),
			'value'	=> $index_size_kb.' KB'
		);
		$settings[] = array(
			'title'	=> $this->__('MongoDB Storage Size'),
			'value'	=> $storage_size_mb.' MB'
		);
		$settings[] = array(
			'title'	=> $this->__('MongoDB File Size'),
			'value'	=> $total_size_mb.' MB'
		);
		return $settings;
	}

	public function time($args = array())
	{
		$query = false;
		$defaults = array(
			'col'	=> 'mbsert'
		);
		$settings = array_merge($defaults, $args);
		if(!self::$db) $this->connect();
		$db = self::$db;
		if(isset($settings['after']) && isset($settings['before']))
		{
			$before = $settings['before']['key'];
			$start = new MongoDate($settings['before']['value']);

			$after = $settings['after']['key'];
			$end = new MongoDate($settings['after']['value']);

			$query = array(
				'$and'	=> array(
					array($before => array('$lte' => $start)),
					array($after => array('$gte' => $end))
				)
			);

		}
		elseif(isset($settings['after']) || isset($settings['before']))
		{
			if(isset($settings['after']))
			{
				$after = $settings['after'];
			}
			else
			{
				$before = $settings['before'];
			}
		}
		$col = $db->$settings['col'];
		return $this->arrayed($col->find($query));
	}

	public function paginate($args = array(), $objects_per_page = 10, $page = 1)
	{
		$objects = $this->count($args);
		$pages = round($objects / $objects_per_page);
		$skip = ($page - 1) * $objects_per_page;
		$args['offset'] = $skip;
		$args['limit'] = $objects_per_page;
		$objs = $this->find($args);
		if(is_array($objs))
		{
			foreach($objs as $key => $obj)
			{
				$objs[$key]['id'] = $this->_id($obj['_id']);
				$objs[$key]['_pages'] = $pages;
				$objs[$key]['_page'] = $page;
			}
			return $objs;
		}
	}

	public function find($args = array())
	{
		$defaults = array(
			'col'		=> 'mbsert',
			'where'		=> array(),
			'regex'		=> false,
			'limit'		=> false,
			'offset'	=> false,
			'order_by'	=> false,
			'order'		=> false,
			'id'		=> false,
			'string_id'	=> false
		);
		$settings = array_merge($defaults, $args);
		if($settings['order_by']){
			if ($settings['order']!='desc') $order_value=1; else $order_value=-1;
			$sort_clause = array($settings['order_by']=>$order_value);
		}else{
			$sort_clause = array();
		}
		if(!self::$db) $this->connect();
		$db = self::$db;
		if($settings['regex']!==false) {
			$regex_object = new MongoRegex($settings['regex']);
			$where = $settings['where'];
			$settings['where'] = array(
				$where => $regex_object
			);
		}
		try{
			if(is_array($settings['col'])){
				$combined_array = false;
				$i = 0;
				foreach($settings['col'] as $this_collection){
					$collection = $db->$this_collection;
					$results = $this->arrayed($collection->find($settings['where'])->sort($sort_clause)->skip($settings['offset'])->limit($settings['limit']));
					if(isset($results)){
						foreach($results as $result){
							$combined_array[$i] = $result;
							$i++;
						}
					}
				}
				return $combined_array;
			}else{
				if(!is_object($db)) {
					$this->error($this->__('MongoDB Not Running'));
				}else{
					if($settings['regex']!==false) {
						$collection = $db->$settings['col'];
						$results = $this->arrayed($collection->find($settings['where'])->sort($sort_clause)->skip($settings['offset'])->limit($settings['limit']));
						return $results;
					}
				}
				$collection = $db->$settings['col'];
				if($settings['id']){
					if($settings['string_id'] != false) $actual_id = array('_id' => $settings['id']);
					else $actual_id = array('_id' => new MongoId($settings['id']));
					$result = $collection->findOne($actual_id);
					return $result;
				}else{
					if($settings['where'] && count($settings['where'])>0){
						/* TODO: FIX THIS MAJOR HACK */
						if(count($settings['where'])==2){
							$i = 0; $keys = array();
							foreach($settings['where'] as $key => $value){
								$keys[$i]['key'] = $key;
								$keys[$i]['value'] = $value;
								$i++;
							};
							$js = 'function() {
								if(this.profile) return this.profile.'.$keys[0]['key'].' == "'.$keys[0]['value'].'" && this.profile.'.$keys[1]['key'].' == "'.$keys[1]['value'].'";
							}';
						}else{
							$js = 'function() {
								if(this) return this.'.key($settings['where']).' == "'.current($settings['where']).'";
							}';
						};
						/* TODO - WHY LIKE THIS ...? */
						$results = $this->arrayed($collection->find(array('$where' => $js))->sort($sort_clause)->skip($settings['offset'])->limit($settings['limit']));
					}else{
						$results = $this->arrayed($collection->find()->sort($sort_clause)->skip($settings['offset'])->limit($settings['limit']));
					}
					return $results;
				}
			}
		}catch(Exception $e){
			return $this->__('Error: ').$e->getMessage();
		}
	}

    public function dump($args = array()){
		$defaults = array(
			'type'		=> 'dump',
			'name'		=> false,
			'format'	=> 'json',
			'limit'		=> 0,
			'where'		=> array()
		);
		$settings = array_merge($defaults, $args);
        // Get list of collections and run export on each!
        $list = self::$db->listCollections();
        $results = false;
        foreach ($list as $collection) {
            $options = array(
                'type'  => 'dump',
                'where' => $settings['where'],
                'limit' => $settings['limit'],
                'format' => $settings['format'],
                'name'  => $collection->getName(),
                'col'  => $collection->getName()
            );
            $progress = $this->export($options);
            $results[] = array(
                'name'      => $collection->getName(),
                'result'    => $progress
            );
        }
        if($results) echo json_encode($results);
    }

	/* NOT SURE WHAT THIS IS FOR ...?
	public function content($args = array()){
		$defaults = array(
			'col'		=> 'content',
			'where'		=> array()
		);
		$settings = array_merge($defaults, $args);
		$options= array(
			'col'	=> $settings['col'],
			'where'	=> $settings['where']
		);
		$content = $this->find($options);
		return $content;
	}
	*/

	public function indexes($args = array()){

		/* THIS ENSURES INDEXES ARE ONLY RUN WHEN VISITING ADMIN PAGES */
		/* TODO: RESEARCH THE CONSEQUENCES AND IMPROVE ACCORDINGLY */
		$defaults = array(
			'indexes'	=> array(
				array(
					'col'		=> 'users',
					'field'		=> 'e',
					'unique'	=> true
				),
				array(
					'col'		=> 'users',
					'field'		=> 'un',
					'unique'	=> true
				),
				array(
					'col'		=> 'users',
					'field'		=> 'p',
					'unique'	=> false
				),
				array(
					'col'		=> 'emails',
					'field'		=> 'e',
					'unique'	=> true
				)
			)
		);
		$settings = array_merge_recursive($defaults, $args);
		if(isset($settings['indexes']) && is_array($settings['indexes'])){
			$settings['indexes'] = $this->apply_filters('db_indexes', $settings['indexes']);
			if(!self::$db) $this->connect();
			$db = self::$db;
			foreach($settings['indexes'] as $index){
				if(isset($index['col'])){ $col = $index['col']; }else{ $col = false; }
				if(isset($index['field'])){ $field = $index['field']; }else{ $col = false; }
				if(isset($index['unique'])){ $unique = $index['unique']; }else{ $unique = false; }
				if(isset($index['order'])){ $order = (int) $index['order']; }else{ $order = 1; }
				if(isset($index['geo'])){ $geo = $index['geo']; }else{ $geo = false; }
				if(isset($index['drop_duplicates'])){ $drop_duplicates = $index['drop_duplicates']; }else{ $drop_duplicates = false; }
				if($drop_duplicates) $drop_duplicates = true;
				if($geo) $geo = true;
				if($col && $field){
					$collection = $db->$col;
					if($geo === true) $collection->ensureIndex(array($field => "2d"));
					else $collection->ensureIndex(array($field => $order), array("unique" => $unique, "dropDups" => $drop_duplicates));
				}
			}
		}
	}

	public function errors($obj = array(), $extras = array(), $key = 'id', $value = false, $col = 'errors')
	{
		$data = array_merge($obj, $extras);
		$check = $this->find(array(
			'col'	=> $col,
			'where'	=> array(
				$key	=> $value
			)
		));
		if(!is_array($check))
		{
			$this->mbsert(array(
				'col'   => $col,
				'obj'   => $data
			));
		}
	}

}