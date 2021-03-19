<?php

namespace JambageCom\Ecorss\View;

/***************************************************************
 *	Copyright notice
 *
 *	(c) 2007 Fabien Udriot <fabien.udriot@ecodev.ch>
 *	All rights reserved
 *
 *	This script is part of the TYPO3 project. The TYPO3 project is
 *	free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	The GNU General Public License can be found at
 *	http://www.gnu.org/copyleft/gpl.html.
 *
 *	This script is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 *	GNU General Public License for more details.
 *
 *	This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Plugin 'RSS services' for the 'ecorss' extension.
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package TYPO3
 * @subpackage ecorss
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

class View implements \TYPO3\CMS\Core\SingletonInterface {

// tx_div2007_phpTemplateEngine {

    public $entries;
    public $data;
    public $parseFunc;
    private $cObj;

    public function __construct ($entries, $data, $parseFunc) {
        $this->entries = $entries;
        $this->data = $data;
        $this->parseFunc = $parseFunc;
        $this->cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
    }
    
	/**
	 * Print the feed's summary.
	 */
	public function printSummary () {
		// Remove script-tags from content
		$pattern[] = '/<( *)script([^>]*)type( *)=( *)([^>]*)>(.*)<\/( *)script( *)>/isU';
		$replace[] = '';

		// Remove event handler
		$pattern[] = '/( *)(on[a-z]{4,10})( *)=( *)"([^"]*)"/isU';
		$replace[] = '';

		// Remove javascript in url, etc
		$pattern[] = '/"( *)javascript( *):([^"]*)"/isU';
		$replace[] = '""';

		// Replaces baseURL link
		$baseURL = $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'];
		if($baseURL) {
			// Replace links
			$pattern[] = "/<a([^>]*) href=\"([^http|ftp|https][^\"]*)\"/isU";
			$replace[] = "<a\${1} href=\"" . $baseURL . "\${2}\"";

			// Replace images
			$pattern[] = "/<img([^>]*) src=\"([^http|ftp|https][^\"]*)\"/";
			$replace[] = "<img\${1} src=\"" . $baseURL . "\${2}\" alt=\${2}";
		}

		$content = preg_replace($pattern, $replace, FrontendUtility::RTEcssText($this->cObj, 'summary'));
    
		print '<![CDATA[' . $content . ']]>';
	}

	/**
	 * Print the current url of the page.
     * @param	array		row of data
	 */
	public function printUrl () {
		print $this->printAsRaw('host');
		$url = $this->printAsText('url');
		$pattern[] = '/\?clear_cache=1/isU';
		$replace[] = '';
		$replace[] = '/\&clear_cache=1/isU';
		$replace[] = '';
		print preg_replace($pattern, $replace, $url);
	}

    public function render ($template) 
    {
		ob_start();                                                              // Run the template ...
		include_once($template);
		$out = ob_get_clean();
		return $out;
    }

    /**
    * Get a string parsed for standard text input (parseFunc).
    *
    * This includes HTMLSPECIALCHARS
    * and parsing of http://xxxx and mailto://xxxx to links.
    *
    * Behaves identical to asHtml() but additionally escapes html special characters.
    *
    * @param	mixed		key of data
    * @param	array		row of data
    * @return	mixed		parsed string
    * @see		asHtml()
    */
    public function printAsText ($key, array $entry = []) {
        $setup = [];
        if(is_array($this->parseFunc)) {
            $setup['parseFunc.'] = $this->parseFunc;
        } elseif($this->parseFunc) {
            $setup['parseFunc'] = $this->parseFunc;
        } else {
            $setup['parseFunc'] = '< lib.parseFunc';
        }

        $setup['value'] = htmlspecialchars($this->printAsRaw($key, $entry));

        return $this->cObj->cObjGetSingle('TEXT', $setup);
    }
    
	/**
	 * Print a raw value from the internal data array by key.
	 *
	 * @param	mixed		key of the internal data array
     * @param	array		row of data
	 * @return	void
	 * @see		asRaw()
	 */
    public function printAsRaw ($key, array $entry = [])
    {
        $result = '';
        if (!empty($entry)) {
            $result = $entry[$key];
        } else {
            $result = $this->data[$key];            
        }
        
        return $result;
    }
}
