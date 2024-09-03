<?php
namespace EarthAsylumConsulting\Traits;

/**
 * Plugin help trait - {eac}Doojigger for WordPress
 *
 * Add help content to plugin administration screens
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0510.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/contextual-help/
 * @used-by		abstract_backend.class.php
 */

trait plugin_help
{
	/**
	 * @var array help tabs, id => [ title , content , priority ]
	 */
	private $plugin_help_content = [];

	/**
	 * @var array help fields, id => [ title , field, content ]
	 */
	private $plugin_help_fields = [];

	/**
	 * @var array help sidebar content
	 */
	private $plugin_help_sidebar = [];

	/**
	 * @var bool help tabs enabled flag
	 */
	private $plugin_help_tabs_enabled = true;

	/**
	 * @var bool help fields enabled flag
	 */
	private $plugin_help_fields_enabled = true;

	/**
	 * @var bool force field help on first tab
	 */
	public $plugin_help_fields_on_first_tab = false;


	/**
	 * plugin_help_enabled - set or test enabled for use
	 *
	 * @param	bool $tabs enable/disable tabs
	 * @param	bool $fields enable/disable fields
	 * @return	bool
	 */
	public function plugin_help_enabled($tabs=null,$fields=null)
	{
		if (is_bool($tabs))
		{
			$this->plugin_help_tabs_enabled 	= $tabs;
			$this->plugin_help_fields_enabled 	= $tabs;
		}
		if (is_bool($fields))
		{
			$this->plugin_help_fields_enabled 	= $fields;
		}
		return $this->plugin_help_tabs_enabled;
	}


	/**
	 * Render the contextual help.
	 * Must be called after 'current_screen', before 'admin_print_styles'.
	 * Called in abstract_backend by the 'current_screen' action.
	 *
	 * @param object $screen current screen
	 * @return void
	 */
	public function plugin_help_render($screen = null): void
	{
		if (!$this->plugin_help_tabs_enabled || empty($this->plugin_help_content)) return;
		if (empty($screen) && !function_exists('get_current_screen')) return;

		\add_action( 'admin_print_styles', 	array( $this, 'plugin_help_render_css' ), 999 );

		$screen = $screen ?? get_current_screen();

		foreach ($this->plugin_help_content as $id => $help)
		{
			$this->plugin_help_render_tab($screen, $id, ...$help);
		}

		if (!empty($this->plugin_help_sidebar))
		{
			$screen->set_help_sidebar( wp_kses_post( wpautop(implode("\n",$this->plugin_help_sidebar),false) ) );
		}

		\add_action('admin_footer', 	array($this,'plugin_help_render_fields'),100);
	}


	/**
	 * Add the contextual help tab.
	 *
	 * @internal
	 *
	 * @param object $screen current screen
	 * @param string $id tab id
	 * @param string $title tab title
	 * @param string $content tab content
	 * @param int $priority priority of the tab (optional, default 10)
	 * @return void
	 */
	public function plugin_help_render_tab(object $screen, string $id, string $title, string $content, int $priority=10): void
	{
		$content = 	"<section id='eac-help-tab-{$id}' class='eac-help-tab-content'>{$content}</section>\n" .
					"<section id='eac-help-field-{$id}' class='eac-help-tab-content eac-help-fields'></section>\n";

		$screen->add_help_tab(
			array(
				'id'      => $id,
				'title'   => $title,
				'content' => wp_kses_post( wpautop($content,false) ),
				'priority'=> $priority,
			)
		);
	}


	/**
	 * Add CSS for help tabs.
	 * Called in plugin_help_render by the 'admin_print_styles' action.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function plugin_help_render_css(): void
	{
		ob_start();
		?>
			.eac-help-tab-content {margin: 0;}
			.eac-help-tab-content small {font-size: 0.9em; margin: 0;}
			.eac-help-tab-content details details blockquote {
				max-height: 20em; overflow: auto;
			}
			.eac-help-tab-content details summary {
				cursor: pointer;  margin: .5em; color: #0073aa; font-weight: 400;
			}
			.eac-help-tab-content details[open] summary {
				border-bottom: solid .5px #aaa;
			}
			.eac-help-field-grid {
				display: grid; grid-template-columns: max-content auto; grid-column-gap: .75em; grid-row-gap: 0;
				padding: 0; margin: 0.5em 1em; max-height: 20em; overflow: auto;
			}
			.eac-help-field-grid details summary {margin: 0; border-bottom: none !important;}
			.eac-help-field-grid div {margin-bottom: 0.5em;}
			.eac-help-field-grid div:nth-child(odd) {font-style: italic; max-width: 14em;}
			.eac-help-field-grid small,.eac-help-field-grid mark {
				color: #777; background-color: inherit; font-size: 0.9em; font-style: normal; font-weight: normal;
			};
		<?php
		$style = ob_get_clean();
		echo "<style id='eac-help-content'>\n".str_replace(["\n","\t"],"",$style)."</style>\n";
	}


	/**
	 * Add field-level help after rendering.
	 * Called in plugin_help_render by the 'admin_footer' action.
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function plugin_help_render_fields(): void
	{
	//	echo "<!-- Help Content : ";print_r($this->plugin_help_content);echo " -->\n";
	//	echo "<!-- Help Fields  : ";print_r($this->plugin_help_fields);echo " -->\n";

		if (empty($this->plugin_help_fields)) return;

		// add field-level help to named help tab
		echo "\n <!-- contextual help tabs/fields -->\n";

		foreach($this->plugin_help_fields as $tab => $helpFields)
		{
			echo "<div class='eac-help-field-grid' id='eac-help-grid-{$tab}'>\n";
			foreach ($helpFields as $help)
			{
				echo "<div>".$help[1]."</div>".
					 "<div>".wp_kses_post($help[2])."</div>\n";
			}
			echo "</div>\n";
		}

		$scriptId = sanitize_title($this->className.'-help');

		$script="function move_help_field(t){try{\n".
				"var h = document.getElementById('eac-help-field-'+t)".
				" || document.querySelector('section.eac-help-fields');\n".
				"if (h.innerHTML=='') h.innerHTML = '<details><summary>On this page</summary></details>';\n".
				"h.firstChild.appendChild( document.getElementById('eac-help-grid-'+t) );\n".
				"}catch(e){console.warn(e,t);}}\n";

		foreach($this->plugin_help_fields as $tab => $helpFields)
		{
			$script .= "move_help_field('$tab')\n";
		}
		// add script to footer
		wp_register_script( $scriptId, false, [], null, true );
		wp_enqueue_script( $scriptId);
		wp_add_inline_script( $scriptId, $script );
	}


	/**
	 * Additional formatting of the help content.
	 * May be overloaded in your plugin
	 *
	 * @param string $content tab content
	 * @return string
	 */
	public function formatPluginHelp(string $content): string
	{
		return $content;
	}


	/**
	 * Add content to the help tabs.
	 *
	 * @param array|string $title tab title or [title,id]
	 * @param array|string $content tab content
	 * @param string|array $header section header or [header,true] (optional)
	 * @param int $priority priority of the tab (optional, default 10)
	 * @return void
	 */
	public function addPluginHelpTab($title, $content, $header=null, int $priority=10): void
	{
		if (!$this->plugin_help_tabs_enabled) return;

		if (is_array($title)) {
			$id 	= $title[1] ?? $title[0];
			$title 	= $title[0];
		} else {
			$id 	= $title;
		}

		$id 		= $this->_plugin_help_id($id);
		$title 		= $this->_plugin_help_title($title);
		$content 	= $this->_plugin_help_content($content);

		if ($header)
		{
			if (!is_array($header)) $header =[$header,false];
			$header[0] = $this->formatPluginHelp(
				$this->_plugin_help_title($header[0])
			);
			$content = "<details" . ($header[1] ? ' open' : '') . ">".
						"<summary>" . $header[0] . "</summary>".
						"<blockquote>" . $content ."</blockquote>".
						"</details>\n";

		}

		if (empty($content))
		{
			unset($this->plugin_help_content[$id]);
		}
		else
		{
			if (isset($this->plugin_help_content[$id]))
			{
				$content = $this->plugin_help_content[$id][1] . $content;
			}
			$this->plugin_help_content[$id] = [$title,$content,$priority];
		}
	}


	/**
	 * Add field-level content to the help tabs.
	 * May occur after help has been rendered.
	 *
	 * @param array|string $titles tab title or [multiple titles to match in priority order]
	 * @param string $label field label
	 * @param array|string $content field content (may be [title=>content])
	 * @return void
	 */
	public function addPluginHelpField($titles, string $label, $content): void
	{
		if (!$this->plugin_help_tabs_enabled || !$this->plugin_help_fields_enabled) return;

		if (is_array($content) && !is_int(key($content)))
		{
			$titles = (array)$titles;
			array_unshift( $titles, key($content) );
			$content = current($content);
		}

		if (is_array($titles))
		{
			foreach ($titles as $title)
			{
				$id = $this->_plugin_help_id($title);
				if (isset($this->plugin_help_content[$id])) break;
			}
		}
		else
		{
			$title	= $titles;
			$id 	= $this->_plugin_help_id($title);
		}
		$title 	= $this->_plugin_help_title($title);

		if ($this->plugin_help_fields_on_first_tab || !isset($this->plugin_help_content[$id]))
		{
			$id = key($this->plugin_help_content);
		}

		$label 		= $this->_plugin_help_content($label);
		$content 	= $this->_plugin_help_content($content);

		$this->plugin_help_fields[$id][] = [$title,$label,$content];
	}


	/**
	 * Add text to the help sidebar.
	 *
	 * @param array|string $content sidebar content
	 * @return void
	 */
	public function addPluginSidebarText($content): void
	{
		if ($content)
		{
			$this->plugin_help_sidebar[] = $this->_plugin_help_content($content);
		}
	}


	/**
	 * Add link to the help sidebar.
	 *
	 * @param string $title link title
	 * @param string $link link url
	 * @param string $tooltip a title tooltip (optional)
	 * @return void
	 */
	public function addPluginSidebarLink(string $title, string $link, string $tooltip=null): void
	{
		if ($tooltip)
		{
			$tooltip = esc_attr__( $tooltip, $this->PLUGIN_TEXTDOMAIN );
		}

		$content = sprintf( '<p><a href="%1$s" data-tooltip="%2$s" title="%2$s" target="_blank">%3$s</a></p>',
			esc_url( $link ),
			$tooltip,
			__(  $this->formatPluginHelp($title), $this->PLUGIN_TEXTDOMAIN )
		);

		$this->plugin_help_sidebar[] = $content;
	}


	/**
	 * Sanitize the help tab id
	 *
	 * @param string $id help tab id
	 * @return string
	 */
	private function _plugin_help_id(string $id): string
	{
		return sanitize_key(
				str_replace( [' ','_','.','&nbsp;'],'-',
					preg_replace('/[\s_][eE]xtension/','',$id)
				));
	}


	/**
	 * Sanitize the help tab title
	 *
	 * @param string $title help tab title
	 * @return string
	 */
	private function _plugin_help_title(string $title): string
	{
		return esc_attr__(
					ucwords(str_replace('_',' ',$title)),
				$this->PLUGIN_TEXTDOMAIN);
	}


	/**
	 * Sanitize the help content
	 *
	 * @param string|array $content help content html
	 * @return string
	 */
	private function _plugin_help_content($content): string
	{
		return implode("\n",array_map(
					function ( $content )
					{
						return __(
							$this->formatPluginHelp(
								preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $content ),
							),$this->PLUGIN_TEXTDOMAIN);
					},
					is_array( $content ) ? $content : [ $content ]
		));
	}
}
