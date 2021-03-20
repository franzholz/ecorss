<?php

namespace JambageCom\Ecorss\Model;

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
 * @author  Xavier Perseguers <xavier@perseguers.ch>
 * @package TYPO3
 * @subpackage ecorss
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class Feed implements \TYPO3\CMS\Core\SingletonInterface {

	public function __construct($controller) {
        $this->controller = $controller;
    }

	/**
	 * Initialize the modem, parse the TS configuration and prepare the list of updated pages for the feed.
	 * return array of entries
	 * @access publi
	 */
	public function load () {
		//init a few variables

		$pidRootline = $this->controller->configurations['pidRootline'];
		$sysLanguageUid = isset($this->controller->configurations['sysLanguageUid']) ? $this->controller->configurations['sysLanguageUid'] : '';
		$author = isset($this->controller->configurations['author.']) ? $this->controller->configurations['author.'] : '';
		$configurations = is_array($this->controller->configurations['select.']) ? $this->controller->configurations['select.'] : [0];
		$limitSQL = isset($this->controller->configurations['numberItems']) ? $this->controller->configurations['numberItems'] : '10';
		$entries = [];

		$link = GeneralUtility::makeInstance('tx_div2007_link');
		$link->noHash();

		foreach ($configurations as $config) {

			// Initialize some variables
			$summary = $title = '';

			/* PROCESS THE TITLE */
			if (isset($config['titleXPath'])) {
				$flexFormField = $config['title'] != '' ? $config['title'] : 'pi_flexform';
				$title .= "EXTRACTVALUE(" . $flexFormField . ",'".$config['titleXPath']."')";
			} else {
				$title = isset($config['title']) ? $config['title'] : 'header';
			}

			/* PROCESS THE SUMMARY */
			if (isset($config['summaryXPath'])) {
				$flexFormField = $config['summary'] != '' ? $config['summary'] : 'pi_flexform';
				$summary .= "EXTRACTVALUE(" . $flexFormField . ",'" . $config['summaryXPath'] . "')";
			} else {
				$summary = isset($config['summary']) ? $config['summary'] : 'bodytext';
			}

			/* PROCESS THE OTHER FIELDS */
			$table = $config['table'] != '' ? $config['table'] : 'tt_content';
			$published = isset($config['published']) ? $config['published'] : 'tstamp';
			$updated = isset($config['updated']) ? $config['updated'] : 'tstamp';
			$uid = isset($config['uid']) ? $config['uid'] : 'uid';
			$headerLayout = $config['table'] == 'tt_content' ? ', header_layout' : '';

			$pid = isset($config['pid']) ? $config['pid'] : 'pid';

			// Added possible author field thanks to Alexandre Morel
			$authorSQL = isset($config['author']) ? ", " . $config['author'] . " as author" : '';

			// Added possible extra fields thanks to Pierre Rossel
			$extraFieldsSQL = isset($config['extraFields']) ? ", " . $config['extraFields'] : '';

			$fieldSQL = $pid . ' as pid, ' . $uid . ' as uid, ' . $title . ' as title, ' . $summary . ' as summary, ' . $published . ' as published, ' . $updated . ' as updated' . $headerLayout . $authorSQL . $extraFieldsSQL;

			/* PROCESS THE CLAUSE */
			$clauseSQL = '1=1 ' . \JambageCom\Div2007\Utility\TableUtility::enableFields($table);

			// Selects some field according to the configuration
			if (isset($config['filterField']) && isset($config['filterInclude'])) {
				$values = explode(',' , $config['filterInclude']);
				foreach ($values as $value) {
					$clauseSQL .= ' AND ' . $config['filterField'] . '="' . trim($value) . '"';
				}
			}
			// Excludes some field according to the configuration
			if (isset($config['filterField']) && isset($config['filterExclude'])) {
				$values = explode(',',$config['filterExclude']);
				foreach ($values as $value) {
					$clauseSQL .= ' AND ' . $config['filterField'] . '!="' . trim($value) . '"';
				}
			}

			// Checks if the page is in the root line
			if ($pidRootline != null) {
				$pages = $this->getAllPages($pidRootline);
				$pageClauseSQL = 'pid=' . $pidRootline;
				foreach ($pages as $page) {
					$pageClauseSQL .= ' OR pid=' . $page['uid'];
				}

				// Adds additional pid's
				if (isset($config['additionalPids']) && $config['additionalPids'] != '') {
					foreach (explode(',', $config['additionalPids']) as $pid) {
						$pageClauseSQL .= ' OR pid=' . $pid;
					}
				}

				$clauseSQL .= ' AND (' . $pageClauseSQL . ')'; #merge of the two clauses
			}

			// Adds additional SQL
			if (isset($config['additionalSQL']) && $config['additionalSQL'] != '') {
				$clauseSQL .= ' ' . $config['additionalSQL'] . ' ';
			}

			// Only return selected language content
			if ($sysLanguageUid != null) {
				$clauseSQL .= ' AND sys_language_uid=' . $sysLanguageUid;
			}

			// Adds custom conditions.
			if (isset($config['where']) ) {
				$clauseSQL .= ' ' . $config['where'];
			}

			if (isset($config['orderBy'])) {
				$order = $config['orderBy'];
			}
			else {
				$order = 'tstamp DESC';
			}

			$debug = isset($config['debug']) ? $config['debug'] : 'false';
			if ($debug == 'true' || $debug == 1) {
				print $GLOBALS['TYPO3_DB']->SELECTquery($fieldSQL, $table, $clauseSQL, '', $order, $limitSQL);
			}
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldSQL, $table, $clauseSQL, '', $order, $limitSQL);

			/* PREPARE THE OUTPUT */
			if ($result) {
				while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result)) {
					// Hide pages that are not visible to everybody
					if ($this->isPageProtected($row['pid'])) {
						continue;
					}

					// Handle the link
					$linkItem = isset($config['linkItem']) ? $config['linkItem'].' ' : 1;

					$url = '';
					if ($linkItem == 'true' || $linkItem == 1) {
                        $parameters = [];
						if ($table == 'tt_content') {	// standard content
							$link->destination($row['pid']);
						} elseif ($table == 'pages'){ // special content from user-configured table
                            $link->destination($row['uid']);
						} else { // special content from user-configured table
							$linkConfig = $config['single_page.'];
							$link->destination($linkConfig['pid']);
							$parameters = [$linkConfig['linkParamUid'] => $row['uid'], 'no_cache' => '1'];
						}

						if (isset($this->controller->configurations['profileAjaxType'])) {
							$parameters = array_merge(
								$parameters,
								['type' => $this->controller->configurations['profileAjaxType']]
							);
						}

						$link->parameters($parameters);

						// domain may be something else. Look for it
						if (isset($config['baseUrl']) && $config['baseUrl'] != '') {
							$domain = $config['baseUrl'];
						}
						else {
							$domain = $this->getDomain($link->destination);
						}

						// Gets the URL
						$url = $domain . $link->makeUrl(false);

						//handle the anchors
						if (!isset($this->controller->configurations['no_anchor'])) {
							$this->controller->configurations['no_anchor'] = 0;
						}
						if ($this->controller->configurations['no_anchor'] != 1) {
							$url .= '#c'.$row['uid'];
						}
					}

					// Handle the default text
					$defaultText = isset($config['defaultText']) ? $config['defaultText'].' ' : '';

					// Handle the index of the array
					$uid = $row['uid'];
					if (strlen($uid) < 5) {
						$uid = str_pad($uid,5,'0');
					} else {
						$uid = substr($uid, 0, 5);
					}

					// Handle empty title or hidden header for table tt_content
					if ((!$row['title'] && $table == 'tt_content') ||
						(isset($row['header_layout']) && $row['header_layout'] == '100')) {
						$this->updateClosestTitle($row, $clauseSQL, $sysLanguageUid);
					}

					// Get author name and email: configuration
					if ($author != null) {
						$author_name = $author['name'];
						$author_email = $author['email'];
					} elseif($authorSQL != null) {
						//print "alexandre";
						$author_name = $row['author'];
						$author_email = '';
					} else {
						list($author_name, $author_email) = $this->getAuthor($row, $sysLanguageUid);
					}

					// Truncates the summary.
					if(isset($config['truncate']) and $config['truncate'] > 0){
						$row['summary'] = $this->truncate($row['summary'], $config['truncate'], ' ...');
					}

					$entry = [
						'title' => $defaultText .$row['title'],
						'updated' => $row['updated'],
						'published' => $row['published'],
						'summary' => $row['summary'],
						'author' => $author_name,
						'author_email' => $author_email,
						'link' => $url
					];

					/* Hook that enable post processing the output) */
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ecorss']['PostProcessingProc'])) {
						$_params = [
							'config' => isset($this->controller->configurations['hook.']) ? $this->controller->configurations['hook.'] : null,
							'row'    => $row,
							'entry'  => &$entry
						];

						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ecorss']['PostProcessingProc'] as $_funcRef) {
							GeneralUtility::callUserFunction($_funcRef, $_params, $this);
						}
					}

					// Number of digit. This number should be verified. Prevent an error with big database.
					// The order of the entries may be wrong in certain case. PHP expects an integer (32 bits) as index in an array but in big dataset, this number may exceed the integer
					$entries[(int)($row['updated'] / 100000) . $uid] = $entry;
				}
			}
			// Sort decreasingly in case it is a union of different arrays
			krsort($entries, SORT_NUMERIC);
		}

		$result = array_splice($entries, 0, $limitSQL);
		return $result;
	}

	/**
	 * Truncates the text according to the length. Cut up the content between to words (at the next space).
	 *
	 * @param	string	$content: input text
	 * @param	int		$int_length
	 * @param	int		$int_length
	 */
	private function truncate ($content, $length, $str_suffixe = ''){
		$content = strip_tags($content);

		//TRUE means the text needs to be cut up
		if (strlen($content) > $length) {
			$content = substr($content, 0, $length);

			// Looking for the next space
			$last_space = strrpos($content, " ");

			// Adds the terminaison
			$content = substr($content, 0, $last_space) . $str_suffixe;
		}
		return $content;
	}

	/**
	 * Look for the real domain name. Useful in multidomain configuration
	 *
	 * @param	int		$pid: the pid of the current page
	 * @return	string	$domain: the target domain name
	 *
	 */
	private function getDomain ($pid) {
		// check wheter we are in a multidomain environment

		//default value
		$domain = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');

		// Looks for existing domain
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_domain', '', '', 'sorting DESC');

		// Looks for the right domain
		if (!empty($records)) {
			foreach($records as $record) {
				$domains[$record['pid']] = $record['domainName'];
			}

			$pids = $GLOBALS['TSFE']->sys_page->getRootLine($pid);
			foreach ($pids as $pid) {
				$uid = $pid['uid'];
				if (isset($domains[$uid])) {
					$protocole = 'http';
					if (preg_match('/https/is', $GLOBALS['_SERVER']['SERVER_PROTOCOL'])) {
						$protocole = 'https';
					}
					$domain = $protocole . '://' . $domains[$uid] . '/';
					break;
				}
			}
		}
		return $domain;
	}

	/**
	 * Return the list of page's pid being descendant of <tt>$pid</tt>.
	 *
	 * @param	integer		$pid: mother page's pid
	 * @param	array		$arrayOfPid: referenced array of children's pid
	 * @access	private
	 * @return	array		Array of all pid being children of <tt>$pid</tt>
	 */
	public function getAllPages ($pid, &$arrayOfPid = []) {
		$pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted = 0 AND hidden = 0 AND pid=' . $pid);
		$arrayOfPid = array_merge($pages, $arrayOfPid);
		if (count($pages) > 0) {
			foreach ($pages as $page) {
				$this->getAllPages($page['uid'], $arrayOfPid);
			}
		}
		return $arrayOfPid;
	}

	/**
	 * Return the closest header for a given content element.
	 * This only works for tt_content table.
	 *
	 * @param	array		$row: SQL row whose title should be updated
	 * @param	string		$clauseSQL: current SQL filtering clause
	 * @param	integer		$sysLanguageUid: <tt>sys_language_uid</tt> when used in a multilingual context
	 * @access	private
	 * @return	string		Closest header for the given element
	 */
	public function updateClosestTitle (&$row, $clauseSQL, $sysLanguageUid = null) {
		$clauseSQL .= ' AND pid=' . $row['pid'] . ' AND sorting < (SELECT sorting FROM tt_content WHERE uid=' . $row['uid'] . ') AND header != \'\'';
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('header', 'tt_content', $clauseSQL, '', 'sorting DESC', 1);
		if ($result) {
			$row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			if ($row2['header']) {
				// Update the title with the header of a previous content element
				$row['title'] = $row2['header'];
			} else {
				// Title cannot be found, use the page's title instead
				$table = 'pages';
				$clauseSQL = 'uid=' . $row['pid'];
				if ($sysLanguageUid != null && $sysLanguageUid > 0) {
					$table = 'pages_language_overlay';
					$clauseSQL .= ' AND sys_language_uid=' . $sysLanguageUid;
				}

				$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title', $table, $clauseSQL);
				if ($result) {
					$row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
					// Update the title with the page's title
					$row['title'] = $row2['title'];
				}
			}
		}
		return $row['title'];
	}

	/**
	 * Return the author name and email for a given content element.
	 * This information is taken from the enclosing page itself.
	 *
	 * @param	array		$row: SQL row whose author should be returned
	 * @param	integer		$sysLanguageUid: <tt>sys_language_uid</tt> when used in a multilingual context
	 * @return	array		author name and email
	 */
	public function getAuthor( &$row, $sysLanguageUid = null) {
		$author = $author_email = '';

		$clauseSQL = 'uid=' . $row['pid'];
		$table = 'pages';
		if ($sysLanguageUid != null && $sysLanguageUid > 0) {
			$table = 'pages_language_overlay';
		}

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('author, author_email', $table, $clauseSQL);
		if ($result) {
			$row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			$author = $row2['author'];
			$author_email = $row2['author_email'];
		}

		if (empty($author)) $author = 'anonymous';

		return [$author, $author_email];
	}

	/**
	 * Check if a page is protected and should not be shown to 'everybody'.
	 *
	 * @param	integer		$pid: page id to be tested
	 * @return	boolean		true if the page should not be disclosed to everybody
	 */
	public function isPageProtected ($pid) {
		$clauseSQL = 'uid=' . $pid;
		$table = 'pages';

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('perms_everybody', $table, $clauseSQL);
		if ($result) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			return !($row['perms_everybody'] == 0 || $row['perms_everybody'] & 1);
		}
		return false;
	}
}
