<?php

class mongobase_theme extends mongobase_mb
{

	protected static $options;
	protected static $env;

	function __construct($options = array(), $key = 'theme')
	{
		$defaults = array(
			'key' => $key
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		// REGISTER MODULE
		parent::__construct($settings, $settings['key']);
	}

	public function pagination($pages, $page, $page_limit = 10, $size = false, $alignment = false)
	{
		if($pages > 0)
		{
			$prev = 1; $next = $pages;
			if($page > 2) $prev = $page - 1;
			if($page < $pages) $next = $page + 1;
			$pagination_class = 'pagination'; if($size) $pagination_class = $pagination_class.' '.$pagination_class.'-'.$size;
			if($alignment) $pagination_class = $pagination_class.' pagination-'.$alignment;
			$html = '<div style="display: block; clear: both; padding: 15px 0; position: relative;">';
			$html.= '<div class="'.$pagination_class.'">';
				$html.= '<ul>';
					$html.= '<li><a href="?p=1">First</a></li>';
					$html.= '<li><a href="?p='.$prev.'">Prev</a></li>';

					$contents = '';
					$got_more = false;
					for($i = 1; $i <= $pages; $i++) {
						$this_page = $i;
						if(round($pages / 2) <= $page) $this_page = $this_page + 1;
						if($i <= $page_limit)
						{
							$class = false;
							if($page == $this_page) $class = 'active';
							$contents.= '<li class="'.$class.'"><a href="?p='.$this_page.'">'.$this_page.'</a></li>';
						}
						else
						{
							$got_more = true;
						}
					}
					if($got_more)
					{
						if(round($pages / 2) <= $page){
							$html.= '<li class="disabled"><span>...</span></li>';
							$html.= $contents;
						}
						else
						{
							$html.= $contents;
							$html.= '<li class="disabled"><span>...</span></li>';
						}
					}

					$html.= '<li><a href="?p='.$next.'">Next</a></li>';
					$html.= '<li><a href="?p='.$pages.'">Last</a></li>';
				$html.= '</ul>';
			$html.= '</div>';
			$html.= '</div>';
			return $html;
		}
	}

	public function navigation($options = array())
	{
		if(!isset($options))
		{
			$settings = array(
				'id'		=> false,
				'defaults'	=> array(
					'top'		=> 'docs',
					'second'	=> 'intro'
				),
				'login'		=> true,
				'lists'		=> array(
					'docs'	=> array(
						'text'		=> _('Documentation'),
						'subnav'	=> array(
							'intro'		=> array(
								'text'		=> _('Introductions'),
								'url'		=> mb_url('')
							),
							'hr1'		=> _('The Basics'),
							'admin'		=> array(
								'text'		=> _('Administration'),
								'url'		=> mb_url('docs/admin')
							),
							'design'	=> array(
								'text'		=> _('Design Tools'),
								'url'		=> mb_url('docs/design')
							),
							'comm'		=> array(
								'text'		=> _('Communication'),
								'url'		=> mb_url('docs/comm')
							),
							'dev'		=> array(
								'text'		=> _('Developer Tools'),
								'url'		=> mb_url('docs/dev')
							)
						)
					),
					'modules'	=> array(
						'text'		=> _('Modules'),
						'subnav'	=> array(
							'core'		=> array(
								'text'		=> _('Core'),
								'url'		=> mb_url('modules/core')
							),
							'admin'		=> array(
								'text'		=> _('Admin'),
								'url'		=> mb_url('modules/admin')
							),
							'ajax'		=> array(
								'text'		=> _('AJAX'),
								'url'		=> mb_url('modules/ajax')
							),
							'auth'		=> array(
								'text'		=> _('Auth'),
								'url'		=> mb_url('modules/auth')
							),
							'db'		=> array(
								'text'		=> _('DB'),
								'url'		=> mb_url('modules/db')
							),
							'forms'		=> array(
								'text'		=> _('Forms'),
								'url'		=> mb_url('modules/forms')
							),
							'mustache'	=> array(
								'text'		=> _('Mustache'),
								'url'		=> mb_url('modules/mustache')
							),
							'theme'		=> array(
								'text'		=> _('Theme'),
								'url'		=> mb_url('modules/theme')
							)
						)
					),
					'tests'	=> array(
						'text'		=> _('Unit Tests'),
						'subnav'	=> array(
							'core'		=> array(
								'text'		=> _('MB Core Tests'),
								'url'		=> mb_url('mb_tests/mongobase_core_tests.php')
							),
							'base'		=> array(
								'text'		=> _('Base Module Tests'),
								'url'		=> mb_url('mb_tests/mongobase_base_tests.php')
							),
							'hr2'		=> _('Module Tests'),
							'admin'		=> array(
								'text'		=> 'Admin Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_admin_tests.php')
							),
							'ajax'		=> array(
								'text'		=> 'AJAX Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_ajax_tests.php')
							),
							'auth'		=> array(
								'text'		=> 'Authentication Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_auth_tests.php')
							),
							'db'		=> array(
								'text'		=> 'Database Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_db_tests.php')
							),
							'form'		=> array(
								'text'		=> 'Form Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_form_tests.php')
							),
							'must'		=> array(
								'text'		=> 'Mustache Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_mustache_tests.php')
							),
							'theme'		=> array(
								'text'		=> 'Theme Tests',
								'url'		=> mb_url('mb_tests/mongobase_module_theme_tests.php')
							)
						)
					),
					'filters'	=> array(
						'text'		=> _('Filters'),
						'url'		=> mb_url('filters')
					),
					'admin'	=> array(
						'text'		=> _('Administration'),
						'url'		=> mb_url('admin')
					)
				)
			);
		}
		else
		{
			$defaults = array(
				'defaults'		=> null,
				'responsive'	=> false,
				'brand'			=> mb_option('mb', 'title', _('MongoBase')),
				'login'			=> false,
				'social'		=> false,
				'lists'			=> array(
					'home'		=> array(
						'text'		=> _('Home'),
						'url'		=> $this::$env->urls->root,
						'id'		=> 'home',
						'class'		=> false,
						'active'	=> true,
						'subnav'	=> null
					)
				),
				'rlist'			=> false
			);
			$settings = array_merge($defaults, $options);
		}
		$directory = $this::$env->dir;
		$actions = $this::$env->actions;
		$slug = $this::$env->slug;

		$settings = $this->apply_filters('mb_theme_navigation', $settings);

		if(!isset($settings['class'])) $settings['class'] = '';
		if(!isset($settings['id'])) $settings['id'] = '';

		if(isset($settings['defaults']) && is_array($settings['defaults']) && count($settings['defaults']) > 0 && count($settings['defaults']) < 3)
		{
			if(!$slug)
			{
				if(isset($settings['defaults']['top'])) $actions[0] = $settings['defaults']['top'];
				if(isset($settings['defaults']['second'])) $actions[1] = $settings['defaults']['second'];
			}
		}

		$class = false;
		if(isset($settings['class']) && $settings['class']) $class = $settings['class'];

		$navigation = false;

		$navigation.= '<nav class="navbar navbar-default '.$settings['class'].'" role="navigation">';

			if((isset($settings['responsive']) && $settings['responsive']) || (isset($settings['brand']) && $settings['brand']))
			{
				$navigation.= '<div class="navbar-header">';

				if(isset($settings['responsive']) && $settings['responsive'])
				{
					$navigation.= '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">';
						$navigation.= '<span class="sr-only">Toggle Navigation</span>';
						$navigation.= '<span class="icon-bar"></span>';
						$navigation.= '<span class="icon-bar"></span>';
						$navigation.= '<span class="icon-bar"></span>';
					$navigation.= '</button>';
				}
				if(isset($settings['brand']) && $settings['brand'])
				{
					$navigation.= '<a class="navbar-brand" href="'.$this::$env->urls->root.'">'.$settings['brand'].'</a>';
				}

				$navigation.= '</div>';
			}
			
			if(isset($settings['responsive']) && $settings['responsive']) $navigation.= '<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">';
				$navigation.= '<ul class="nav navbar-nav">';
				
					if(isset($settings['lists']) && is_array($settings['lists']) && count($settings['lists']) > 0)
					{
						foreach($settings['lists'] as $top_level_key => $top_level)
						{
							if(isset($top_level['text']) && $top_level['text'])
							{
								$text = $top_level['text'];
								if(!isset($top_level['url'])) $top_level['url'] = '#';
								if(!isset($top_level['active'])) $top_level['active'] = false;
								if(!isset($top_level['class'])) $top_level['class'] = false;
								if(!isset($top_level['id'])) $top_level['id'] = false;

								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$top_level['class'] = $top_level['class'].' dropdown';
								}

								if($actions[0] == $top_level_key)
								{
									$top_level['active'] = true;
								}
								else
								{
									$top_level['active'] = false;
								}

								$active = $this->apply_filters('mb_theme_navigation_active', $top_level);
								if(is_array($active)) $active = $active['active'];

								if($active) $navigation.= '<li class="active';
								else $navigation.= '<li class="';
								if($top_level['class']) $navigation.= ' '.$top_level['class'];
								if($top_level['id']) $navigation.= ' id="'.$top_level['id'].'"';

								$navigation.= '">';

								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= '<a class="dropdown-toggle fa" data-toggle="dropdown" href="'.$top_level['url'].'">';
								}
								else
								{
									$navigation.= '<a href="'.$top_level['url'].'" class="fa">';
								}

								if(isset($top_level['icon'])) $navigation.= '<i class="icon-'.$top_level['icon'].'"></i>';

								$navigation.= $top_level['text'];
								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= ' <b class="caret"></b>';
								}
								$navigation.= '</a>';
								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= '<ul class="dropdown-menu">';
									foreach($top_level['subnav'] as $second_level_key => $second_level)
									{
										if(is_array($second_level))
										{
											if(isset($second_level['text']) && $second_level['text'])
											{
												if(!isset($second_level['url'])) $second_level['url'] = '#';
												if(!isset($second_level['active'])) $second_level['active'] = false;
												if(!isset($second_level['class'])) $second_level['class'] = false;

												if(isset($actions) && is_array($actions) && count($actions) > 1)
												{
													if($actions[0] == $top_level_key && $actions[1] == $second_level_key)
													{
														$second_level['active'] = true;
													}
													else
													{
														$second_level['active'] = false;
													}
												}

												$navigation.= '<li class="';
												if($second_level['active']) $navigation.= 'active';
												if($second_level['class']) $navigation.= ' '.$second_level['class'];
												$navigation.= '">';
												$navigation.= '<a href="'.$second_level['url'].'">'.$second_level['text'].'</a></li>';
											}
										}
										else
										{
											$navigation.= '<li class="nav-header">'.$second_level.'</li>';
										}
									}
									$navigation.= '</ul>';
								}
								$navigation.= '</li>';
							}
						}
					};
					$navigation.= '</ul>';
				
				$navigation.= '</ul>';

				if(isset($settings['rlist']) && is_array($settings['rlist']) && count($settings['rlist']) > 0)
				{
					$navigation.= '<ul class="nav navbar-nav navbar-right">';

						foreach($settings['rlist'] as $top_level_key => $top_level)
						{
							if(isset($top_level['text']) && $top_level['text'])
							{
								$text = $top_level['text'];
								if(!isset($top_level['url'])) $top_level['url'] = '#';
								if(!isset($top_level['active'])) $top_level['active'] = false;
								if(!isset($top_level['class'])) $top_level['class'] = false;
								if(!isset($top_level['id'])) $top_level['id'] = false;

								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$top_level['class'] = $top_level['class'].' dropdown';
								}

								if($actions[0] == $top_level_key)
								{
									$top_level['active'] = true;
								}
								else
								{
									$top_level['active'] = false;
								}

								$active = $this->apply_filters('mb_theme_navigation_active', $top_level);
								if(is_array($active)) $active = $active['active'];

								if($active) $navigation.= '<li class="active';
								else $navigation.= '<li class="';
								if($top_level['class']) $navigation.= ' '.$top_level['class'];
								if($top_level['id']) $navigation.= ' id="'.$top_level['id'].'"';

								$navigation.= '">';

								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= '<a class="dropdown-toggle fa" data-toggle="dropdown" href="'.$top_level['url'].'">';
								}
								else
								{
									$navigation.= '<a href="'.$top_level['url'].'" class="fa">';
								}

								if(isset($top_level['icon'])) $navigation.= '<i class="icon-'.$top_level['icon'].'"></i>';

								$navigation.= $top_level['text'];
								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= ' <b class="caret"></b>';
								}
								$navigation.= '</a>';
								if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
								{
									$navigation.= '<ul class="dropdown-menu">';
									foreach($top_level['subnav'] as $second_level_key => $second_level)
									{
										if(is_array($second_level))
										{
											if(isset($second_level['text']) && $second_level['text'])
											{
												if(!isset($second_level['url'])) $second_level['url'] = '#';
												if(!isset($second_level['active'])) $second_level['active'] = false;
												if(!isset($second_level['class'])) $second_level['class'] = false;

												if(isset($actions) && is_array($actions) && count($actions) > 1)
												{
													if($actions[0] == $top_level_key && $actions[1] == $second_level_key)
													{
														$second_level['active'] = true;
													}
													else
													{
														$second_level['active'] = false;
													}
												}

												$navigation.= '<li class="';
												if($second_level['active']) $navigation.= 'active';
												if($second_level['class']) $navigation.= ' '.$second_level['class'];
												$navigation.= '">';
												$navigation.= '<a href="'.$second_level['url'].'">'.$second_level['text'].'</a></li>';
											}
										}
										else
										{
											$navigation.= '<li class="nav-header">'.$second_level.'</li>';
										}
									}
									$navigation.= '</ul>';
								}
								$navigation.= '</li>';
							}
						}

					$navigation.= '</ul>';
				}

			if(isset($settings['responsive']) && $settings['responsive']) $navigation.= '</div>';

			if(isset($settings['login']) && $settings['login'])
			{
				$navigation.= '<form class="navbar-form navbar-right" action="'.mb_url('admin').'">';
					$navigation.= '<div class="form-group">';
						$navigation.= '<input type="text" class="form-control" placeholder="Email">';
					$navigation.= '</div>';
					$navigation.= '<div class="form-group">';
						$navigation.= '<input type="password" class="form-control" placeholder="Password">';
					$navigation.= '</div>';
					$navigation.= '<button type="submit" class="btn btn-default">Submit</button>';
				$navigation.= '</form>';
			}

		$navigation.= '</nav>';

		return $navigation;







		if(isset($settings['responsive']) && $settings['responsive'])
		{
			$navigation.= '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
		}

		if(isset($settings['brand']) && $settings['brand'])
		{
			$navigation.= '<a class="brand" href="'.$this::$env->urls->root.'">'.$settings['brand'].'</a>';
		}
		if(isset($settings['responsive']) && $settings['responsive'])
		{
			$navigation.= '<div class="nav-collapse collapse">';
		}
		$navigation.= '<ul id="'.$settings['id'].'" class="nav '.$settings['class'].'">';
		if(isset($settings['lists']) && is_array($settings['lists']) && count($settings['lists']) > 0)
		{
			foreach($settings['lists'] as $top_level_key => $top_level)
			{
				if(isset($top_level['text']) && $top_level['text'])
				{
					$text = $top_level['text'];
					if(!isset($top_level['url'])) $top_level['url'] = '#';
					if(!isset($top_level['active'])) $top_level['active'] = false;
					if(!isset($top_level['class'])) $top_level['class'] = false;
					if(!isset($top_level['id'])) $top_level['id'] = false;

					if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
					{
						$top_level['class'] = $top_level['class'].' dropdown';
					}

					if($actions[0] == $top_level_key)
					{
						$top_level['active'] = true;
					}
					else
					{
						$top_level['active'] = false;
					}

					$active = $this->apply_filters('mb_theme_navigation_active', $top_level);
					if(is_array($active)) $active = $active['active'];

					if($active) $navigation.= '<li class="active';
					else $navigation.= '<li class="';
					if($top_level['class']) $navigation.= ' '.$top_level['class'];
					if($top_level['id']) $navigation.= ' id="'.$top_level['id'].'"';

					$navigation.= '">';

					if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
					{
						$navigation.= '<a class="dropdown-toggle fa" data-toggle="dropdown" href="'.$top_level['url'].'">';
					}
					else
					{
						$navigation.= '<a href="'.$top_level['url'].'" class="fa">';
					}

					if(isset($top_level['icon'])) $navigation.= '<i class="icon-'.$top_level['icon'].'"></i>';

					$navigation.= $top_level['text'];
					if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
					{
						$navigation.= ' <b class="caret"></b>';
					}
					$navigation.= '</a>';
					if(isset($top_level['subnav']) && is_array($top_level['subnav']) && count($top_level['subnav']) > 0)
					{
						$navigation.= '<ul class="dropdown-menu">';
						foreach($top_level['subnav'] as $second_level_key => $second_level)
						{
							if(is_array($second_level))
							{
								if(isset($second_level['text']) && $second_level['text'])
								{
									if(!isset($second_level['url'])) $second_level['url'] = '#';
									if(!isset($second_level['active'])) $second_level['active'] = false;
									if(!isset($second_level['class'])) $second_level['class'] = false;

									if(isset($actions) && is_array($actions) && count($actions) > 1)
									{
										if($actions[0] == $top_level_key && $actions[1] == $second_level_key)
										{
											$second_level['active'] = true;
										}
										else
										{
											$second_level['active'] = false;
										}
									}

									$navigation.= '<li class="';
									if($second_level['active']) $navigation.= 'active';
									if($second_level['class']) $navigation.= ' '.$second_level['class'];
									$navigation.= '">';
									$navigation.= '<a href="'.$second_level['url'].'">'.$second_level['text'].'</a></li>';
								}
							}
							else
							{
								$navigation.= '<li class="nav-header">'.$second_level.'</li>';
							}
						}
						$navigation.= '</ul>';
					}
					$navigation.= '</li>';
				}
			}
		};
		$navigation.= '</ul>';

		if(isset($settings['social']) && is_array($settings['social']))
		{
			$navigation.= '<ul class="pull-right fa" style="list-style: none; font-size: 40px; padding: 12px 5px 0 0">';
				foreach($settings['social'] as $handle => $url)
				{
					$navigation.= '<li><a href="'.$url.'" target="_blank"><i class="icon-'.$handle.'"></i></a></li>';
				}
			$navigation.= '</ul>';
		}

		if(isset($settings['login']) && $settings['login'])
		{
			$navigation.= '<form class="navbar-form pull-right" action="'.mb_url('admin').'">
			  <input class="span2" type="text" placeholder="'._('Email').'">
			  <input class="span2" type="password" placeholder="'._('Password').'">
			  <button type="submit" class="btn">'._('Sign-In').'</button>
			</form>';
		}
		if(isset($settings['responsive']) && $settings['responsive'])
		{
			$navigation.= '</div>';
		}
		return $navigation;

	}

	public function layout($options = array())
	{
		$defaults = array(
			'type'	=> 'sales'
		);
		// Merge options and defaults
		$settings = array_merge($defaults, $options);

		$layout = false;
		if(isset($settings['type'])) $layout = $settings['type'];
		if($layout)
		{
			$contents = parent::cascade('layouts', $settings['type']);
		}
		echo $contents;
	}
}
