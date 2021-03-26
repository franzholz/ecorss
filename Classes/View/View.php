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

 
use Symfony\Component\Mime\MimeTypes;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

class View implements \TYPO3\CMS\Core\SingletonInterface {
    public $entries;
    public $data;
    public $parseFunc;
    private $cObj;
    private static $mimeTypes;

    public function __construct ($entries, $data, $parseFunc) {
        $this->entries = $entries;
        $this->data = $data;
        $this->parseFunc = $parseFunc;
        $this->cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
        self::$mimeTypes = new MimeTypes();
    }

    /**
    * Print the media enclosure
    * @param	array		row of data
    */
    public function printEnclosure (array $entry = [])
    {
		$baseUrl = $entry['domain'];

		if($baseUrl) {
            $imageUrl = '';
            $imagePath = '';
            $bytes = 0;
            $contentType = '';

            if (
                isset($entry['image']) &&
                !empty($entry['image'])
            ) {
                $imageUrl = $entry['domain'] . 'fileadmin' . $entry['image']['identifier'];
                $bytes = $entry['image']['size'];
                $contentType = $entry['image']['mime_type'];
            } else {
                // parse images
                preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i', $this->asRaw('summary', $entry), $matches);
                if (!empty($matches)) {
                    $elements = $matches[1];
                    if (!empty($elements)) {
                        $imageUrl = $elements['0'];
                        if ($imageUrl != '') {
                            $imagePath = PATH_site . str_replace($data['host'], '', $imageUrl);
                            $ext = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $bytes = filesize($imagePath);
                            $contentType = self::$mimeTypes->getMimeTypes($ext[0]);
                        }
                    }
                }
            }

            if ($imageUrl != '') {                
                $content = '<enclosure url="' . $imageUrl . '" length="' . $bytes . '" type="' . $contentType . '"/>';
                echo $content;
            }
        }
    }

	/**
	 * Print the feed's summary.
    * @param	array		row of data
	 */
	public function printSummary (array $entry = [])
	{
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
		$baseUrl = $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'];
		if($baseUrl) {
			// Replace links
			$pattern[] = "/<a([^>]*) href=\"([^http|ftp|https][^\"]*)\"/isU";
			$replace[] = "<a\${1} href=\"" . $baseUrl . "\${2}\"";

			// Replace images
			$pattern[] = "/<img([^>]*) src=\"([^http|ftp|https][^\"]*)\"/";
			$replace[] = "<img\${1} src=\"" . $baseUrl . "\${2}\" alt=\${2}";
		}

		$content = preg_replace($pattern, $replace, FrontendUtility::RTEcssText($this->cObj, $this->asRaw('summary', $entry)));
    
		print '<![CDATA[' . $content . ']]>';
	}

	/**
	 * Print the current url of the page.
     * @param	array		row of data
	 */
	public function printUrl ()
	{
		print $this->asRaw('host');
		$url = $this->asText('url');
		$pattern[] = '/\?clear_cache=1/isU';
		$replace[] = '';
		$replace[] = '/\&clear_cache=1/isU';
		$replace[] = '';
		print preg_replace($pattern, $replace, $url);
	}

    public function render ($template) 
    {
		ob_start();                                                             
		// Run the template ...
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
    public function asText ($key, array $entry = [])
    {
        $setup = [];
        if(is_array($this->parseFunc)) {
            $setup['parseFunc.'] = $this->parseFunc;
        } elseif($this->parseFunc) {
            $setup['parseFunc'] = $this->parseFunc;
        } else {
            $setup['parseFunc'] = '< lib.parseFunc';
        }

        $setup['value'] = htmlspecialchars($this->asRaw($key, $entry));
        $result = $this->cObj->cObjGetSingle('TEXT', $setup);
        return $result;
    }
    
	/**
	 * Print a raw value from the internal data array by key.
	 *
	 * @param	mixed		key of the internal data array
     * @param	array		row of data
	 * @return	void
	 * @see		asRaw()
	 */
    public function asRaw ($key, array $entry = [])
    {
        $result = '';
        if (!empty($entry) && !empty($entry[$key])) {
            $result = $entry[$key];
        } else {
            $result = $this->data[$key];            
        }
        
        return $result;
    }

    public function printAsRaw ($key, array $entry = []) 
    {
        print $this->asRaw($key, $entry);
    }

    public function printAsText ($key, array $entry = []) 
    {
        print $this->asText($key, $entry);
    }
}

