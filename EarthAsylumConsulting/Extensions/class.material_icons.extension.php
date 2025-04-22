<?php
namespace EarthAsylumConsulting\Extensions;

if (! class_exists(__NAMESPACE__.'\material_icons', false) )
{
	/**
	 * Extension: material_icons - Add/enable Google's Material Icons to WordPress - {eac}Doojigger for WordPress
	 *
	 * @category	WordPress Plugin
	 * @package		{eac}Doojigger\Extensions
	 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
	 * @copyright	Copyright (c) 2025 EarthAsylum Consulting <www.EarthAsylum.com>
	 * @link		https://eacDoojigger.earthasylum.com/
	 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
	 */

	class material_icons extends \EarthAsylumConsulting\abstract_extension
	{
		/**
		 * @var string extension version
		 */
		const VERSION	= '25.0417.1';

		/**
		 * @var string additional styling
		 */
		const MD_STYLE	= "
/* Rules for sizing the icon. */
.material-icons.md-18 { font-size: 18px; }
.material-icons.md-24 { font-size: 24px; }
.material-icons.md-36 { font-size: 36px; }
.material-icons.md-48 { font-size: 48px; }
/* Rules for using icons as black on a light background. */
.material-icons.md-dark { color: rgba(0, 0, 0, 0.54); }
.material-icons.md-dark.md-inactive { color: rgba(0, 0, 0, 0.26); }
/* Rules for using icons as white on a dark background. */
.material-icons.md-light { color: rgba(255, 255, 255, 1); }
.material-icons.md-light.md-inactive { color: rgba(255, 255, 255, 0.3); }
";


		/**
		 * constructor method
		 *
		 * @param 	object	$plugin main plugin object
		 * @return 	void
		 */
		public function __construct($plugin)
		{
			parent::__construct($plugin, self::ALLOW_ADMIN | self::ALLOW_NETWORK | self::DEFAULT_DISABLED);

			$this->registerExtension( $this->className );
			add_action('admin_init', function()
			{
				// Register plugin options when needed
				$this->add_action( "options_settings_page", array($this, 'admin_options_settings') );
			});
		}


		/**
		 * register options on options_settings_page
		 *
		 * @access public
		 * @return void
		 */
		public function admin_options_settings()
		{
			/* register this extension with group name on default tab, and settings fields */
			$this->registerExtensionOptions( $this->className,
				[
					'_materialicons'		=> array(
									'type'		=> 	'display',
									'label'		=> 	'Google\'s Material Icons',
									'default'	=> 	"To use Google's Material Icons, add the class name 'material-icons' to an element enclosing the icon name.",
									'tooltip'	=>	"&lt;span class='material-icons'&gt;face&lt;/span&gt;<br/>".
													"<span class='material-icons'>face</span><br/>".
													"&lt;span class='material-icons md-dark'<br/> style='background: lightblue;'&gt;face&lt;/span&gt;<br/>".
													"<span class='material-icons md-dark' style='background: lightblue;'>face</span><br/>".
													"&lt;span class='material-icons md-light'<br/> style='background: darkblue;'&gt;face&lt;/span&gt;<br/>".
													"<span class='material-icons md-light' style='background: darkblue;'>face</span>",
									'info'		=>	"See the <a href='https://google.github.io/material-design-icons/' target='_blank'>Material Icons Guide</a>".
													" and the <a href='https://fonts.google.com/icons?selected=Material+Icons' target='_blank'>Material Icons Library</a>",
									'help'		=> 	"<details><summary>What are material icons?</summary>".
													"<q>Material design system icons are simple, modern, friendly, and sometimes quirky. ".
													"Each icon is created using our design guidelines to depict in simple and minimal forms the universal concepts used commonly throughout a UI. ".
													"Ensuring readability and clarity at both large and small sizes, these icons have been optimized for beautiful display on all common platforms and display resolutions. ".
													"</q> -- <cite><a href='https://google.github.io/material-design-icons/' target='_blank'>Material Icons Guide</a></cite></details>".
													"<details><summary>Additional styling can be achieved using these included class rules...</summary>".
													"<pre><code>".trim(self::MD_STYLE)."</code></pre></details>".
													"[info]",
					),
				]
			);
		}


		/**
		 * Called after instantiating, loading extensions and initializing
		 *
		 * @see https://codex.wordpress.org/Plugin_API
		 *
		 * @return	void
		 */
		public function addActionsAndFilters(): void
		{
			parent::addActionsAndFilters();

			$addStyle = $this->plugin->minifyString(self::MD_STYLE);

			\add_action( ($this->is_admin() ? 'admin' : 'wp').'_print_styles', 	function() use($addStyle)
				{
					wp_register_style('material-icons', '//fonts.googleapis.com/icon?family=Material+Icons', [], null );
					wp_enqueue_style('material-icons');
					wp_add_inline_style('material-icons', $addStyle);
				}
			);
		}
	}
}
/**
 * return a new instance of this class
 */
if (isset($this)) return new material_icons($this);
?>
