<?php
namespace EarthAsylumConsulting\Traits;

/**
 * html input field methods - {eac}Doojigger for WordPress
 *
 * Adds html fields with html display, post processing, sanitization and validation.
 * Used by admin extensions outside of options/settings page. Uses private settings_options_page methods.
 *
 * @category	WordPress Plugin
 * @package		{eac}Doojigger\Traits
 * @author		Kevin Burkholder <KBurkholder@EarthAsylum.com>
 * @copyright	Copyright (c) 2024 EarthAsylum Consulting <www.EarthAsylum.com>
 * @version		24.0502.1
 * @link		https://eacDoojigger.earthasylum.com/
 * @see 		https://eacDoojigger.earthasylum.com/phpdoc/
 */

trait html_input_fields
{
	/**
	 * adds <section><header>...</header><fieldset>
	 * must close </fieldset> & </section> after adding fields
	 *
	 * @param	string 	$groupName option group name
	 * @param	array 	$groupMeta meta-data for $group
	 * @return	string
	 */
	public function html_input_section(string $groupName, array &$groupMeta): string
	{
		ob_start();
		$groupName = $this->plugin->standardizeOptionGroup($groupName);
		$this->plugin->options_settings_page_section($groupName, $groupMeta);
		return ob_get_clean();
	}


	/**
	 * adds <div><label></label></div> <div><input...></div>
	 * includes <input> field and contextual help.
	 *
	 * @param	string 	$fieldName option/field name
	 * @param	array 	$fieldMeta meta-data for $fieldName
	 * @param	mixed 	$fieldValue current value for $fieldName
	 * @param	string	$width optional, override default field width (columns)
	 * @param	string	$height optional, override default field height (rows)
	 * @return	string
	 */
	public function html_input_block(string $fieldName, array $fieldMeta, $fieldValue, $width='50', $height='4'): string
	{
		ob_start();
		$this->plugin->options_settings_page_block($fieldName, $fieldMeta, $fieldValue, $width, $height);
		return ob_get_clean();
	}


	/**
	 * adds a field <input> html
	 * not to  be used  with html_input_block.
	 *
	 * @param	string 	$fieldName option/field name
	 * @param	array 	$fieldMeta meta-data for $fieldName
	 * @param	mixed 	$fieldValue current value for $fieldName
	 * @param	string	$width optional, override default field width (columns)
	 * @param	string	$height optional, override default field height (rows)
	 * @return	string
	 */
	public function html_input_field(string $fieldName, array $fieldMeta, $fieldValue, $width='50', $height='4'): string
	{
		ob_start();
		$this->plugin->options_settings_page_field($fieldName, $fieldMeta, $fieldValue, $width, $height);
		/**
		 * filter {classname}_html_input_field_{fieldName} customize field input html
		 * @param	string	$html current html for field
		 * @param	string	$fieldName option name
		 * @param	array	$fieldMeta option meta data
		 * @param	mixed	$fieldValue current option value
		 * @return	string	new html for field
		 */
		return $this->apply_filters( "html_input_field_{$fieldName}", ob_get_clean(), $fieldName, $fieldMeta, $fieldValue );
	}


	/**
	 * add contextual help from meta data
	 * not to  be used  with html_input_block.
	 *
	 * @param	string|array 	$helpTabs [$groupname,$tabname] - uses first found in help tabs
	 * @param	string			$fieldName option name
	 * @param	array			$fieldMeta option meta data
	 * @return	void
	 */
	public function html_input_help($helpTabs, string $fieldName, array $fieldMeta): void
	{
		$this->plugin->options_settings_page_help($helpTabs,$fieldName,$fieldMeta);
	}


	/**
	 * public shortcut to options_settings_page_sanitize to be used by external code
	 *
	 * @param	mixed	$values posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$fieldMeta option meta data
	 * @return	mixed	sanitized option value(s)
	 */
	public function html_input_sanitize($values, string $fieldName, array $fieldMeta)
	{
		$fieldValue = $this->plugin->options_settings_page_sanitize(wp_unslash($values),$fieldName,$fieldMeta);
		/**
		 * filter {classname}_html_input_sanitize_{fieldName} customize field input html
		 * @param	mixed	$fieldValue current option value
		 * @param	string	$fieldName option name
		 * @param	array	$fieldMeta option meta data
		 * @return	string	new html for field
		 */
		return $this->apply_filters( "html_input_sanitize_{$fieldName}", $fieldValue, $fieldName, $fieldMeta );
	}


	/**
	 * public shortcut to options_settings_page_validate to be used by external code
	 *
	 * @param	mixed	$values posted option value(s)
	 * @param	string	$fieldName option name
	 * @param	array	$fieldMeta option meta data
	 * @return	mixed	validated option value(s)
	 */
	public function html_input_validate($values, string $fieldName, array $fieldMeta)
	{
		$fieldValue = $this->plugin->options_settings_page_validate(wp_unslash($values),$fieldName,$fieldMeta);
		/**
		 * filter {classname}_html_input_validate_{fieldName} customize field input html
		 * @param	mixed	$fieldValue current option value
		 * @param	string	$fieldName option name
		 * @param	array	$fieldMeta option meta data
		 * @return	string	new html for field
		 */
		return $this->apply_filters( "html_input_validate_{$fieldName}", $fieldValue, $fieldName, $fieldMeta );
	}


	/**
	 * enques/loads options_settings_page stylesheet
	 *
	 * @param 	bool 	$asTabs true to include html_input_as_tabs()
	 * @param 	string 	$makeVisible css selector to make hidden visible after convert to tabs
	 * @param 	bool 	$jquery true to load jQuery/jQuery-ui
	 * @return 	string 	the stylesheet id
	 */
	public function html_input_style($asTabs=false,$makeVisible='',$jquery=true): string
	{
		$styleId = $this->plugin->options_settings_page_style();
		if ($jquery)
		{
			$this->html_input_jquery();
		}
		if ($asTabs)
		{
			$this->html_input_as_tabs($makeVisible);
		}
		return $styleId;
	}


	/**
	 * Get admin color variables
	 *
	 * @return	string the root style variables
	 */
	public function html_input_admin_style(): string
	{
		return $this->plugin->options_settings_page_admin_style();
	}


	/**
	 * enques/loads jQuery/jQuery-ui
	 *
	 * @return 	void
	 */
	public function html_input_jquery(): void
	{
		$this->options_settings_page_jquery();
	}


	/**
	 * When using html_input_section(), html_input_block(), convert sections to tabs.
	 * @example: add_action( 'admin_enqueue_scripts', array($this, 'html_input_as_tabs') );
	 * @example: $this->html_input_as_tabs('#make-this-visible');
	 *
	 * @param 	string 	$makeVisible css selector to make visible after convert to tabs
	 * @return 	string 	the javascript id
	 */
	public function html_input_as_tabs($makeVisible=''): string
	{
		// CSS for tabs
		ob_start();
		?>
			/* convert settings-grid to tabs */
			section.settings-grid {clear: both;}
			header.settings-grid-container {display: block; float:left; padding: 2px 0 0;}
			header.settings-grid-container details summary {list-style: none;}
			header.settings-grid-container div {display: none;}
			fieldset.settings-closed {display:none; transform:none;}
			fieldset.settings-opened {transform:none;}
		<?php
		$style = ob_get_clean();
		$styleId = sanitize_title($this->plugin->className.'-settings');
		wp_add_inline_style( $styleId, str_replace("\t","",trim($style)) );

		// JS for tabs
		ob_start();
		?>
			/* convert settings-grid to tabs */
			document.addEventListener('DOMContentLoaded',function()
			{
				var tabs = document.querySelector('.tab-container');
				if (!tabs) return;
				/* find all header tags */
				document.querySelectorAll('header.settings-grid-container').forEach(function(header)
				{
					/* mark detail tag as tab, move header to tab container */
					header.firstElementChild.classList.add('nav-tab','button-primary');
					tabs.appendChild(header);
					/* add  click event */
					header.addEventListener('click', function(event)
					{
						/* remove active from all header detail tags */
						document.querySelectorAll('header.settings-grid-container').forEach(function(e) {
							e.firstElementChild.classList.remove('active');
						});
						/* add active to this header detail tag */
						header.firstElementChild.classList.add('active');
						/* close all fieldset tags */
						document.querySelectorAll('fieldset.settings-opened').forEach(function(e) {
							e.classList.replace('settings-opened','settings-closed');
						});
						/* open corresponding fieldset tag */
						document.querySelector('fieldset.'+header.dataset.name)
							.classList.replace('settings-closed','settings-opened');
					});
				});
				/* make selector visible */
				if ('<?php echo $makeVisible ?>') {
					document.querySelectorAll('<?php echo $makeVisible ?>').forEach(function(e) {
						e.style.visibility = 'visible';
					});
				}
				tabs.firstElementChild.click();
			});
		<?php
		$script = ob_get_clean();
		$scriptId = sanitize_title($this->plugin->className.'-astabs');
		wp_register_script( $scriptId, false, [], null, true  );
		wp_enqueue_script( $scriptId );
		wp_add_inline_script( $scriptId, $this->plugin->minifyString($script) );
		return $scriptId;
	}
}
