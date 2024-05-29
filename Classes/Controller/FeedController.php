<?php

namespace JambageCom\Ecorss\Controller;


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
 * Plugin 'RSS Services' for the 'ecorss' extension.
 *
 * @author	Fabien Udriot <fabien.udriot@ecodev.ch>
 * @package TYPO3
 * @subpackage ecorss
 */

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;


class FeedController {
	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public $cObj;

	public function __construct () {

		$this->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
	}

	/**
	 * Add a feed to the HTML header. Typically it is a link like <link rel="alternate" type="application/atom+xml" title="..." href="..." />
	 *
	 * @param	string	$content: Not used.
	 * @param	array	$configurations: Plugin configuration
	 * @access	public
	 */
	public function add ($content, $configurations)
	{
		$htmlHeader = '';
		//loop around the feed
		foreach ($configurations as $config) {
			if (is_array($config)){

				if (isset($config['typeNum'])) {
					$title = isset($config['title']) ? $config['title'] : '';
					$feed = isset($config['feed']) ? $config['feed'] : 'atom';
					switch($feed) {
						case 'rss' :
							$feed = 'application/rss+xml';
							break;
						case 'atom' :
						default :
							$feed = 'application/atom+xml';
					}

					# Define the URL of the feed
					$conf['data'] = 'leveluid:0';
					$rootPid = $this->cObj->stdWrap('', $conf); //return the id of the root page
					$feedURL = $this->cObj->getTypoLink_URL($rootPid, array('type' => $config['typeNum']));

					# Define the <link>
					$htmlHeader .= '<link rel="alternate" type="' . $feed . '" title="' . $title . '" href="' . $feedURL . '" />' . chr(10);
				} else {
                    throw new \RuntimeException(
'<div style="color:red"><b>plugin ecorss error</b>Parameter typeNum is missing in TypoScript for "page". Try something like this in setup: page.headerData.xxx.myFeed.typeNum = yyy'.'</div>');
                }
			}
		}

        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addHeaderData($htmlHeader);

		/*
		 * feed example :
		 * http://www.oreillynet.com/pub/feed/20 (atom)
		 * http://www.oreillynet.com/pub/feed/20?format=rss1
		 * http://www.oreillynet.com/pub/feed/20?format=rss2
		 */
	}

	/**
	 * Display a XML feed. The main job of the extension.
	 *
	 * @param	string	$content: Not used
	 * @param	array	$configurations: Plugin configuration
	 * @access	public
	 */
	public function display ($content, $configurations)
	{
		$TSconfig = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_ecorss.']['controller.']['feed.'];

		if($TSconfig === null){
			throw new \RuntimeException('<h1>Ecorss</h1> You forgot to include the static template "EcoRSS: rss services (ecorss)" in your root template in onglet "Includes"');
		}
		$TSconfig['configurations.'] = array_merge($TSconfig['configurations.'], $configurations);
		$content = $this->main(null, $TSconfig);
		return $content;
	}

	/**
	 * Default action of this class
	 *
	 * @access	public
	 */
	/**
	 * Main method. Call this from TypoScript by a USER cObject.
	 */
	public function main ($content, $configurations)
	{
        if (isset($configurations['configurations.'])) {
            $configurations = $configurations['configurations.'];
        }
		$cacheContent = null;
		$output = '';

		// Cache mechanism
        $cacheFrontend = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('hash');
        $normalizedParams = $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams');

		$hash = md5(serialize($configurations) . $GLOBALS['TSFE']->type);
		$cacheId = 'Ecorss-Feed-' . $GLOBALS['TSFE']->type;
		if(!isset($configurations['cache_period'])){
			$configurations['cache_period'] = 3600;
		}
		// Clear the cache whenever special parameters are given
		if(
			GeneralUtility::_GP('clear_cache') == 1
		){
            $cacheFrontend->flushByTag($cacheId);
		}

        $cacheContent = $cacheFrontend->get($hash);

        /*
        * true, when the content is hold in the cache system
        * false, when the cache has expired or no cache is present
        */
        if (!empty($cacheContent)) {
            $output = $cacheContent;
        } else {
            // Finding class-names
            $model = GeneralUtility::makeInstance(\JambageCom\Ecorss\Model\Feed::class);
            $data = [];
            $data['title'] = $configurations['title'];
            $data['subtitle'] = $configurations['subtitle'];
            $data['lang'] = isset($configurations['lang']) ? $configurations['lang'] : 'de-DE';
            $data['host'] = isset($configurations['host']) ? $configurations['host'] : $normalizedParams->getSiteUrl();

            // Sanitize the host's value
            if (
                strpos(
                $data['host'],
                'http' . ( $normalizedParams->isHttps() ? 's' : '') . '://'
                ) !== 0   // GeneralUtility::getIndpEnv('TYPO3_SSL')
            ) {
                $data['host'] = 'https://' . $data['host'];
            }
            if (substr($data['host'], -1) == '/') {
                $data['host'] = substr($data['host'], 0, strlen($data['host']) - 1);
            }

            $data['url'] = $normalizedParams->getRequestUri(); // GeneralUtility::getIndpEnv('REQUEST_URI');
            $entries = $model->load($configurations);

            // ... and the view
            $view = GeneralUtility::makeInstance(
                \JambageCom\Ecorss\View\View::class,
                $entries,
                $data,
                ($configurations['parseFunc.'] ?? $configurations['parseFunc'])
            );
            $template = '';
            $pathTemplateDirectory = $configurations['pathToTemplateDirectory'];

            switch ($configurations['feed']) {
                case 'rss':
                    $template = $pathTemplateDirectory . '/' .  $configurations['rssTemplate'];
                    break;
                case 'atom':
                default:
                    $template = $pathTemplateDirectory . '/' .  $configurations['atomTemplate'];
                    break;
            }
            $template = GeneralUtility::getFileAbsFileName($template);
            $encoding = isset($configurations['encoding']) ? $configurations['encoding'] : 'UTF-8';
            $output = '<?xml version="1.0" encoding="' . $encoding . '" ?>' . chr(10);
            $output .= $view->render($template);
            $cacheContent =
                $cacheFrontend->set(
                    $hash,
                    $output,
                    [$cacheId],
                    $configurations['cache_period']
                );
        }
        return $output;
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer ()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    }
}

