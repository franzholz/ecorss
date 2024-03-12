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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;

use JambageCom\Div2007\Utility\FileAbstractionUtility;
use JambageCom\Div2007\Utility\FrontendUtility;


class Feed implements SingletonInterface {

	/**
	 * Initialize the modem, parse the TS configuration and prepare the list of updated pages for the feed.
	 * return array of entries
	 * @access publi
	 */
	public function load ($configurations)
	{
		//init a few variables
		$cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$pidRootline = $configurations['pidRootline'] ?? '';
		$sysLanguageUid = $configurations['sysLanguageUid'] ?? '';
		$author = $configurations['author.']) ?? '';
		$entries = [];

		$databaseConfig = [];
		if (isset($configurations['select.'])) {
            $databaseConfig = $configurations['select.'];
        }
		$limitSQL = isset($configurations['numberItems']) ? intval($configurations['numberItems']) : '10';
		if (!empty($databaseConfig)) {
            $limitSQL = intval($limitSQL / count($databaseConfig) + 1);
		}

		foreach ($databaseConfig as $configKey => $config) {
			// Initialize some variables
			$summary = $title = '';

			/* PROCESS THE TITLE */
			if (isset($config['titleXPath'])) {
				$flexFormField = $config['title'] != '' ? $config['title'] : 'pi_flexform';
				$title .= "EXTRACTVALUE(" . $flexFormField . ",'" . $config['titleXPath'] . "')";
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
			$image = isset($config['image']) ? $config['image'] : '';
			$published = isset($config['published']) ? $config['published'] : 'tstamp';
			$updated = isset($config['updated']) ? $config['updated'] : 'tstamp';
			$uid = isset($config['uid']) ? $config['uid'] : 'uid';
			$headerLayout = $config['table'] == 'tt_content' ? ', header_layout' : '';

			$pid = isset($config['pid']) ? $config['pid'] : 'pid';

			// Added possible author field thanks to Alexandre Morel
			$authorSQL = isset($config['author']) ? ", " . $config['author'] . " as author" : '';

			// Added possible extra fields
			$extraFieldsSQL = isset($config['extraFields']) ? ', ' . $config['extraFields'] : '';

			$fieldSQL = $pid . ' as pid, ' . $uid . ' as uid, ' . $title . ' as title, ' . $summary . ' as summary, ' . ($image != '' ? $image . ' as image, ' : '') . $published . ' as published, ' . $updated . ' as updated' . $headerLayout . $authorSQL . $extraFieldsSQL;

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
					$linkDestination = '';

					// Handle the link
					$linkItem = isset($config['linkItem']) ? $config['linkItem'] . ' ' : 1;
					$url = '';
					if ($linkItem == 'true' || $linkItem == 1) {
                        $parameters = [];
						if ($table == 'tt_content') {	// standard content
							$linkDestination = $row['pid'];
						} else if ($table == 'pages') { // special content from user-configured table
                            $linkDestination = $row['uid'];
						} else if (isset($config['single_page.'])) { // special content from user-configured table
							$linkConfig = $config['single_page.'];
							$linkDestination = $linkConfig['pid'] ?? 0;
							$parameters = [$linkConfig['linkParamUid'] ?? '' => $row['uid']];
							if (
                                isset($linkConfig['linkParam']) &&
                                $linkConfig['linkParam'] != ''
                            ) {
                                $parts = explode('=', $linkConfig['linkParam']);
                                if (
                                    is_array($parts) &&
                                    count($parts) == 2
                                ) {
                                    $parameters[$parts[0]] = $parts[1];
                                }
							}
						} else {
                            $linkDestination = $row['pid'];
						}

						if (isset($configurations['profileAjaxType'])) {
							$parameters = array_merge(
								$parameters,
								['type' => $configurations['profileAjaxType']]
							);
						}

						$url =
							FrontendUtility::getTypoLink_URL(
								$cObj,
								$linkDestination,
								$parameters,
								'',
								[]
							);

                        $no_anchor = 0;
                                //handle the anchors
						if (isset($config['no_anchor'])) {
							$no_anchor = intval($config['no_anchor']);
						}
						if (!$no_anchor) {
							$url .= '#c' . $row['uid'];
						}
					}

					// Handle the default text
					$defaultText = isset($config['defaultText']) ? $config['defaultText'].' ' : '';

					// Handle the index of the array
					$uid = $row['uid'];
					if (strlen($uid) < 5) {
						$uid = str_pad($uid, 5, '0');
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
						list($author_name, $author_email) =
                            $this->getAuthor($row, $sysLanguageUid);
					}

					// Truncates the summary.
					if(isset($config['truncate']) and $config['truncate'] > 0) {
						$row['summary'] = $this->truncate($row['summary'], $config['truncate'], ' ...');
					}
					$entry = [
						'title' => $defaultText . $row['title'],
						'updated' => $row['updated'],
						'published' => $row['published'],
						'summary' => $row['summary'],
						'author' => $author_name,
						'author_email' => $author_email,
						'link' => $url,
						'domain' => $domain
					];

					if (isset($row['image'])) {
                       $imageUidRows =  FileAbstractionUtility::getFileRecords(
                            $table,
                            $config['image'],
                            [$row['uid']]
                        );

                        if (!empty($imageUidRows)) {
                            $imageUidRow = current($imageUidRows);
                            $imageUid = $imageUidRow['uid_local'];
                            $factory = GeneralUtility::makeInstance(ResourceFactory::class);
                            $imageRecord = $factory->getFileObject($imageUid);
                            if (!empty($imageRecord)) {
                                $entry['image'] = $imageRecord->getProperties();
                            }
                        }
					}

					/* Hook that enable post processing the output) */
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ecorss']['PostProcessingProc'])) {
						$_params = [
							'config' => isset($configurations['hook.']) ? $configurations['hook.'] : null,
							'row'    => $row,
							'entry'  => &$entry
						];

						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ecorss']['PostProcessingProc'] as $_funcRef) {
							GeneralUtility::callUserFunction($_funcRef, $_params, $this);
						}
					}

					$key = (int) $row['updated'] . sprintf('%06s', $uid);
					$entries[$key] = $entry;
				}
			}
			// Sort decreasingly in case it is a union of different arrays
			krsort($entries, SORT_NUMERIC);
		}

		return $entries;
	}

	/**
	 * Truncates the text according to the length. Cut up the content between to words (at the next space).
	 *
	 * @param	string	$content: input text
	 * @param	int		$int_length
	 * @param	int		$int_length
	 */
	private function truncate ($content, $length, $str_suffixe = '')
	{
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
	 * Return the list of page's pid being descendant of <tt>$pid</tt>.
	 *
	 * @param	integer		$pid: mother page's pid
	 * @param	array		$arrayOfPid: referenced array of children's pid
	 * @access	private
	 * @return	array		Array of all pid being children of <tt>$pid</tt>
	 */
	public function getAllPages ($pid, &$arrayOfPid = [])
	{
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
	 * This only works for the table tt_content.
	 *
	 * @param	array		$row: SQL row whose title should be updated
	 * @param	string		$clauseSQL: current SQL filtering clause
	 * @param	integer		$sysLanguageUid: <tt>sys_language_uid</tt> when used in a multilingual context
	 * @access	private
	 * @return	string		Closest header for the given element
	 */
	public function updateClosestTitle (&$row, $clauseSQL, $sysLanguageUid = null)
	{
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
					$clauseSQL .= ' AND sys_language_uid=' . intval($sysLanguageUid);
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
	public function getAuthor( $row, $sysLanguageUid = null)
	{
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

		if (empty($author)) {
			$author = 'anonymous';
		}

		return [$author, $author_email];
	}

	/**
	 * Check if a page is protected and should not be shown to 'everybody'.
	 *
	 * @param	integer		$pid: page id to be tested
	 * @return	boolean		true if the page should not be disclosed to everybody
	 */
	public function isPageProtected ($pid)
	{
		$clauseSQL = 'uid=' . intval($pid);
		$table = 'pages';

		$result = $GLOBALS['TYPO3_DB']->exec_SELECTquery('perms_everybody', $table, $clauseSQL);
		if ($result) {
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($result);
			return !($row['perms_everybody'] == 0 || $row['perms_everybody'] & 1);
		}
		return false;
	}
}
