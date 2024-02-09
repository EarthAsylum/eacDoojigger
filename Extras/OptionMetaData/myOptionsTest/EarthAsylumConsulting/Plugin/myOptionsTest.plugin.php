<?php
/**
 * EarthAsylum Consulting {eac}Doojigger derivative
 *
 * @category	WordPress Plugin
 * @package		myOptionsTest, {eac}Doojigger derivative
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2023 EarthAsylum Consulting <www.earthasylum.com>
 * @version		1.x
 */

namespace EarthAsylumConsulting\Plugin;

class myOptionsTest extends \EarthAsylumConsulting\abstract_context
{
	/**
	 * @trait methods for contextual help tabs
	 */
 	use \EarthAsylumConsulting\Traits\plugin_help;

	/**
	 * @var array of html input types
	 */
	const INPUT_TYPES = [
		// html
		'button',
		'checkbox',
		'color',
		'date',
		'datetime-local',
		'email',
		'file',
		'hidden',
		'image',
		'month',
		'number',
		'password',
		'radio',
		'range',
		'reset',
		'search',
		'select',
		'submit',
		'tel',
		'text',
		'textarea',
		'time',
		'url',
		'week',
		// custom
		'help',
		'html',
		'codeedit-js',
		'codeedit-css',
		'codeedit-html',
		'codeedit-php',
		'custom',
	];

	/**
	 * constructor method
	 *
	 * @param array $header plugin header passed from loader script
	 * @return void
	 */
	public function __construct(array $header)
	{
		parent::__construct($header);

		$this->logAlways('version '.$this->getVersion().' '.wp_date('Y-m-d H:i:s',filemtime(__FILE__)),__CLASS__);

		/* we only need to do this on our admin settings page */
		if ($this->is_admin())
		{
			// Register plugin options
			$this->add_action( "options_settings_page", 		array($this, 'admin_options_settings') );
			// Add contextual help
			$this->pluginHelpEnabled(true,true);  				// turn on help system, with field-level help
			$this->add_action( 'options_settings_help', 		array($this, 'admin_options_help') );
		}
	}


	/**
	 * register options on options_settings_page
	 *
	 * @access public
	 * @return void
	 */
	public function admin_options_settings()
	{
		$options = [];

		foreach (self::INPUT_TYPES as $type)
		{
			$typeId = str_replace('-','_',$type);
			/* define the input meta-data array */
			$options[ "input_{$typeId}" ] = array(
							'type'			=> 	$type,
							'title'			=> 	"HTML input type: {$type}",
							'label'			=> 	ucwords(str_replace('-',' ',$type)),
							'before'		=>	'<span class="dashicons dashicons-arrow-left-alt2"></span>',
                    		'options'       =>  [$type=>$type, 'option2'=>'2', 'option3'=>'3'],
							'default'		=> 	$type,
							'after'			=>	'<span class="dashicons dashicons-arrow-right-alt2"></span>',
							'info'			=> 	"Saved option name: '".$this->prefixOptionName("input_{$typeId}")."'",
						//	'class'			=>	"{$type}_class",
						//	'style'			=>	"max-width: 50em;",
							/* attributes as a string */
							'attributes'	=>	"placeholder='{$type}' alt='{$type} input' title='input type: {$type}'",
							/* attributes as array of strings */
						//	'attributes'	=>	[ "placeholder='{$type}'", "alt='{$type} input'", "title='input type: {$type}'" ],
							/* attributes as associative array */
						//	'attributes'	=>	[ 'placeholder'=>$type, 'alt'=>"{$type} input", 'title'=>"input type: {$type}" ],
							/* attributes as array of [strings] */
						//	'attributes'	=> 	[ ["data-1"], ["data-2"], ["data-3"=>"data-3"] ],
							'sanitize'		=>	[ $this,'sanitize_callback' ],
							'filter'		=>	[ FILTER_CALLBACK, ['options'=>[$this,'filter_callback']] ],
							'validate'		=>	[ $this,'validate_callback' ],
						//  contextual help using meta ([title],[type],[info]) macros
							'help'			=>	"<details><summary>[title]</summary>This field is an HTML input type '[type]'<br>[info]</details>",
			);

			/* display values when changed */
			if (in_array($type,['color','date','datetime-local','month','time','week']))
			{
				$options["input_{$typeId}"]['attributes'] = ['oninput'=>"input_{$typeId}_show.value = this.value"	];
				$options["input_{$typeId}"]['after'] .= 	"<output name='input_{$typeId}_show' for='input_{$typeId}' style='padding:2em;color:blue;'>...</output>";
			}

			/* add output display and formatted data-points to our range input */
			if ($type == 'range')
			{
				$options["input_{$typeId}"]['attributes'] = ['min="0"', 'max="10"','step="1"', "list='input_{$typeId}_ticks'",
															 'oninput'=>"input_{$typeId}_show.value = this.value"	];
				$options["input_{$typeId}"]['default'] = 5;
				$options["input_{$typeId}"]['after'] .= 	"<output name='input_{$typeId}_show' for='input_{$typeId}' style='padding:2em;color:blue;'></output>".
															"<datalist id='input_{$typeId}_ticks'>".
																'<option value="0" label="0"></option>'.
																'<option value="1"></option>'.
																'<option value="2" label="2"></option>'.
																'<option value="3"></option>'.
																'<option value="4" label="4"></option>'.
																'<option value="5"></option>'.
																'<option value="6" label="6"></option>'.
																'<option value="7"></option>'.
																'<option value="8" label="8"></option>'.
																'<option value="9"></option>'.
																'<option value="10" label="10"></option>'.
															'</datalist>';
			}
		}

		/* register this plugin with options */
        $this->registerPluginOptions('plugin_settings',$options);

		/* filters to handle 'custom' input field ('input_custom is field name)*/
		$this->add_filter( 'options_form_input_input_custom',	array($this, 'options_form_input_custom'), 10, 4 );
		$this->add_filter( 'options_form_post_input_custom', 	array($this, 'options_form_post_custom'), 10, 4 );

		/* filter to handle 'file' input field ('input_file is field name)*/
		$this->add_filter( 'options_form_post_input_file', 		array($this, 'options_form_post_file'), 10, 4 );

		/* filter to sanitize all fields */
		$this->add_filter( 'sanitize_option', 					array($this, 'sanitize_options'), 10, 4 );

		/* action after form posted and fields updated */
	//	$this->add_action( 'options_form_post', 				array($this, 'my_options_form_post') );
	}


	/**
	 * Add help tab on admin page
	 *
	 * @return	void
	 */
	public function admin_options_help()
	{
		ob_start();
		?>
			Lorem ipsum dolor sit amet, consectetur adipiscing elit,
			sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
			Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
			Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
			Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
		<?php

		$content = ob_get_clean();

		//  add a tab - tab_name , content , <details> heading (optional), priority (optional)
		$this->addPluginHelpTab($this->className, $content, ['My Options Test','open']);

		// add sidebar text/html
		$this->addPluginSidebarText('<h4>For more information:</h4>');

		// add sidebar link - title , url , tooltip (optional)
		$this->addPluginSidebarLink(
			"<span class='dashicons dashicons-editor-help'></span>Custom Plugins",
			'https://eacdoojigger.earthasylum.com/eacdoojigger/',
			"Custom Plugin Derivatives"
		);
	}


	/**
	 * Add filters and actions - called from main plugin
	 *
	 */
	public function addActionsAndFilters(): void
	{
		parent::addActionsAndFilters();

		/* custom stylesheet action - add formatting for our input_range data-points */
		$this->add_action('admin_enqueue_styles', function($styleId)
		{
			$style =
				"#input_range {width: 80%; max-width: 30em;}".
				"#input_range_ticks {".
					"display: flex; width: 80%; max-width: 38em;".
					"justify-content: space-between;".
					"font-size: 0.85em; color:blue;".
					"padding-left: 2em;".
				"}\n";

			wp_add_inline_style( $styleId, $style );
		});
	}


	/**
	 * options_form_input_{$fieldName} filter
	 *
	 * @param	string	$html current html for field
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$value current option value
	 * @return	string	new html for field
	 */
	public function options_form_input_custom($html, $fieldName, $metaData, $value)
	{
		// The default for custom is:
		//	<blockquote>[title]</blockquote>
		//	<code type='custom'>requires: add_filter('myOptionsTest_options_form_input_{$fieldName}','custom_input_function',10,4)</code>
		//	<cite>[info]</cite>

		// replace the <code>...</code> part of the html
		$html = preg_replace("/<code type='custom'>.*<\/code>/m",
					"<mark>Custom input field</mark>".
					"<input type='{$metaData['type']}' name='{$fieldName}' id='{$fieldName}' ".
						"value='{$value}' size='{$metaData['width']}'{$metaData['attributes']} />",
					$html);
		// return the updated input html wrapped in a styled <div>
		return "<div class='custom-example' style='border: solid 1px yellow; padding: .5em; background: #ddd;'>" .
				$html .
				"</div>";
	}


	/**
	 * options_form_post_{$fieldName} filter
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	new option value(s)
	 */
	public function options_form_post_custom($value, $fieldName, $metaData, $priorValue)
	{
		// if no change, just return the value
		if ($value == $priorValue) return $value;

		// sanitize/validate (or otherwise process) the value before it is saved to the database
		return $value;
	}


	/**
	 * options_form_post_{$fieldName} filter
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	new option value(s)
	 */
	public function options_form_post_file($value, $fieldName, $metaData, $priorValue)
	{
		if (! isset($value['error']) && $value['type'] == 'application/json')
		{
			// valid upload with expected file type, $values['file'] is pathname
			if ($data = wp_json_file_decode($values['file'],['associative'=>true]))
			{
				// do something here...
			}
			unlink($value['file']);
			return $value;
		}

		$this->add_option_error(
			$aOptionKey,
			sprintf('%s : Input file could not be processed.',$aOptionMeta['label'])
		);
		unlink($values['file']);
		return $value;
	}


	/**
	 * sanitize_option filter
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	new option value(s)
	 */
	public function sanitize_options($value, $fieldName, $metaData, $priorValue)
	{
		switch ($fieldName)
		{
			case 'input_number':
				// validate $value
					// add warning notice using helper method (uses transient to survive page reload)
					$this->add_option_warning(
						$fieldName,
						$fieldName.": {$value} May be the Answer to the Ultimate Question of Life, the Universe, and Everything.",
					);
				break;
			case 'input_range':
				// validate $value
					// add error notice using helper method (uses transient to survive page reload)
					$this->add_option_error(
						$fieldName,
						$fieldName.": {$value} May be the Answer to the Ultimate Question of Life, the Universe, and Everything.",
					);
				break;
			default:
				// validate $value
		}

	//	$this->logData([$_POST[$fieldName],$value],$fieldName);
    	return $value;
	}


	/**
	 * option sanitize callback
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	sanitized/validate value
	 */
	public function sanitize_callback($value, $fieldName, $metaData, $priorValue)
	{
		return $value;
	}


	/**
	 * filter callback filter a value
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	sanitized/validate value
	 */
	public function filter_callback($value, $fieldName, $metaData, $priorValue)
	{
		// validate the value and display a notification
		if (is_numeric($value))
		{
			// add admin notice using helper method (does not use transient)
			$this->add_admin_notice(
				$fieldName.": {$value} May be the Answer to the Ultimate Question of Life, the Universe, and Everything.",
				'notice',
				"(but we don't know for sure, maybe {$priorValue})"
			);
		}
		return $value;
	}


	/**
	 * option validate callback
	 *
	 * @param	mixed	$value posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$metaData option meta data
	 * @param	mixed	$priorValue prior option value
	 * @return	mixed	sanitized/validate value
	 */
	public function validate_callback($value, $fieldName, $metaData, $priorValue)
	{
		return $value;
	}


	/**
	 * options_form_post action
	 *
	 * @param	array	$postArray array of $fieldName => $metaData
	 * @return	void
	 */
	public function my_options_form_post($postArray)
	{
		foreach ($postArray as $fieldName => $metaData)
		{
			if ($metaData['postValue'] != $metaData['priorValue'])
			{
			//	echo "<p>{$fieldName} has changed from {$metaData['priorValue']} to {$metaData['postValue']}</p>";
			}
		}
	}
}
