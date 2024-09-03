<?php
namespace EarthAsylumConsulting\Traits;

/**
 * code_editor trait - Load CodeMirror and/or WP_editor html editor
 * with consistant parameters
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0509.1
 */
trait code_editor
{
	/**
	 * @var array CodeMirror defaults
	 */
	private static $CE_CODEMIRROR_OPTIONS	= [
		'lineWrapping' 			=> false,
		'gutters'				=> ['CodeMirror-lint-markers'],
	    'autoRefresh'			=> true,
	];

	/**
	 * @var array TinyMCE defaults
	 */
	private static $CE_TINYMCE_OPTIONS		= [
		'plugins' 				=> 'lists,link,wpautoresize',
		'wp_autoresize_on' 		=> true,
		'autoresize_min_height' => 20,
		'autoresize_max_height' => 300,
		'autoresize_on_init' 	=> true,
	//	'toolbar1' 				=> 'styleselect,|,indent,outdent,|,bullist,numlist,|,link,unlink,|,undo,redo',
		'toolbar1'				=> 'formatselect,bold,italic,bullist,numlist,blockquote,'.
									'alignleft,aligncenter,alignright,'.
									'link,strikethrough,removeformat,outdent,indent,undo,redo',
		'toolbar2' 				=> '',
	];

	/**
	 * @var array QuickTags defaults
	 */
	private static $CE_QUICKTAGS_OPTIONS	= [
		'buttons'				=>'p,strong,em,link,block,del,ins,img,ul,ol,li,code,var',
	];

	/**
	 * @var bool did we enqueue
	 */
	private static $CE_CODEEDIT_ENQUEUED 	= false;


	/**
	 * enqueue code mirror
	 *
	 * @param string $styles optional code editor styling
	 * @param array $options optional code editor settings
	 * @return void
	 */
	public function codeedit_enqueue(string $styles = '',array $options = []): void
	{
		if (self::$CE_CODEEDIT_ENQUEUED) return;
		self::$CE_CODEEDIT_ENQUEUED = true;

		wp_enqueue_style( 'wp-codemirror' );

		ob_start();
		?>
			.CodeMirror {
				font-size: 13px;
				max-width: 50em;
				height: auto; min-height: 3em;
				border-radius: 4px !important;
			}
			.CodeMirror, .wp-editor-container {
				border: .75px solid var(--eac-admin-base,#8c8f94) !important;
			}
			.CodeMirror .CodeMirror-linenumber {
				color: var(--eac-admin-icon,#0073AA) !important;
			}
			.mce-toolbar .mce-ico, .mce-toolbar .mce-btn .mce-txt {
			    color: var(--eac-admin-icon,#8c8f94);
    		}
			.wp-editor-wrap {
				max-width: 50em;
			}
			.wp-editor-wrap .ed_button {
				min-width:auto;
				color: var(--eac-admin-icon,#8c8f94) !important;
			}
			.wp-editor-wrap .wp-switch-editor {
				top: 1.5px;
				border: .75px solid var(--eac-admin-base,#8c8f94) !important;
			}
			.wp-editor-wrap .wp-media-buttons .button {
				margin-bottom: 0;
				color: var(--eac-admin-highlight,#0073AA);
			}
			.html-active .switch-html, .tmce-active .switch-tmce {
				color: var(--eac-admin-highlight,#0073AA) !important;
				border-bottom-color: transparent !important;
			}
			.wp-editor-wrap textarea {
				border: none;;
			}
			.wp-editor-wrap iframe {
				width: 99% !important;
			}
		<?php
		if (!empty($styles)) echo $styles;
		$style = ob_get_clean();
		wp_add_inline_style('wp-codemirror', eacDoojigger()->minifyString($style));

		wp_enqueue_script('jquery');
		wp_enqueue_script('code-editor');

		$cm_options = array_replace(self::$CE_CODEMIRROR_OPTIONS,$options);
		$cm_settings = [
			'html' 	=> wp_enqueue_code_editor(['codemirror'=>$cm_options, 'type' => 'text/html']),
			'css' 	=> wp_enqueue_code_editor(['codemirror'=>$cm_options, 'type' => 'text/css']),
			'js' 	=> wp_enqueue_code_editor(['codemirror'=>$cm_options, 'type' => 'text/javascript']),
			'php' 	=> wp_enqueue_code_editor(['codemirror'=>$cm_options, 'type' => 'text/x-php']),
		];
		wp_localize_script('code-editor', 'cm_settings', $cm_settings);

		ob_start();
		?>
			document.addEventListener('DOMContentLoaded',function()
			{
				// initialize code-editor
				['html','js','css','php'].forEach(function(type) {
					document.querySelectorAll('textarea.codeedit-'+type).forEach(function(textarea) {
						textarea.cmEditor = wp.codeEditor.initialize(textarea, cm_settings[type]);
					});
				});
				// quicktags editor buttons
				if (typeof QTags != 'undefined') {
					//				 id		 	button	open		close 		key, 	title, 	priority
					QTags.addButton( 'h2_tag', 	'h2', 	'<h2>', 	'</h2>', 	'', 	'', 	1 );
					QTags.addButton( 'h3_tag', 	'h3', 	'<h3>', 	'</h3>', 	'', 	'', 	2 );
					QTags.addButton( 'h4_tag', 	'h4', 	'<h4>', 	'</h4>', 	'', 	'', 	3 );
					QTags.addButton( 'p_tag', 	'p', 	'<p>', 		'</p>', 	'', 	'', 	4 );
				}
			});
		<?php
		$script = ob_get_clean();
		wp_add_inline_script( 'code-editor', eacDoojigger()->minifyString($script) );
	}


	/**
	 * add code mirror textarea
	 *
	 * @param string $name field name/id
	 * @param string $value field value
	 * @param string $type code editor type (js, css, html, php)
	 * @param string $class optional class name(s)
	 * @param string $attributes optional field attributes
	 * @return string textarea field
	 */
	public function codeedit_get_codemirror(
		string $name,
		string $value = '',
		string $type = 'html',
		string $class = '',
		string $attributes = ''
	): string
	{
		$this->codeedit_enqueue();

		$class 	= trim("{$class} code-editor codeedit-{$type}");
		$rows 	= (strpos($attributes,'rows=') === false) ? "rows='2'" : "";
		$cols 	= (strpos($attributes,'cols=') === false) ? "cols='50'" : "";
		$atts 	= trim("{$rows} {$cols} {$attributes}");

		return "<textarea class='{$class}' name='{$name}' id='{$name}' {$atts}>".
					html_entity_decode($value)."</textarea>";
	}


	/**
	 * add wp_editor
	 *
	 * @param string $name field name/id
	 * @param string $value field value
	 * @param string $class optional class name(s)
	 * @param string $wpoptions optional override wp_editor options
	 * @param string $tinymce optional override tinymce options
	 * @param string $quicktags optional override quicktags options
	 * @return string textarea field
	 */
	public function codeedit_get_wpeditor(
		string $name,
		string $value = '',
		string $class = '',
		array $wpoptions = [],
		array $tinymce = [],
		array $quicktags = []
	): string
	{
		$this->codeedit_enqueue();

		$class 	= trim("{$class} wp-editor");
		// https://www.tiny.cloud/docs/tinymce/latest/table/
		ob_start();
		wp_editor(
			html_entity_decode( $value ),
			$name,
			array_replace(
				[
					'wpautop'		=> false,
					'textarea_rows' => 5,
					'media_buttons' => true,
					'tinymce'		=> array_replace(self::$CE_TINYMCE_OPTIONS,$tinymce),
					'quicktags'		=> array_replace(self::$CE_QUICKTAGS_OPTIONS,$quicktags),
					'editor_class'	=> $class,
				],
				$wpoptions
			)
		);
		return ob_get_clean();
	}
}
