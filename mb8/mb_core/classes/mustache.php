<?php

// Include the mustache Class
require_once(dirname(__FILE__).'/vendors/Mustache.php');

class mongobase_mustache extends mongobase_mb
{

	function __construct($options = array(), $key = 'mustache')
	{
		$defaults = array(
			'key' => $key
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);
	}

    public function render($options = array())
    {
            $defaults = array(
				'content'	=> false,
				//-> CAUSE LOOP 'data'		=> $this->data()
				'data'		=> false
            );
            $settings = array_merge($defaults, $options);
			//if(!$settings['data']) $settings['data'] = $this->data();
			if(!$settings['data']) $settings['data'] = false;
            //$settings = array_merge_recursive_distinct($defaults, $options);

            $template = new MustachePHP();

            $html = false;

            $html = $template->render($settings['content'], $settings['data']);
            return $html;
    }
}
