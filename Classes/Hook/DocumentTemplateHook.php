<?php
namespace TYPO3\ExlPageAdvanced\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Romain CANON <romain.canon@exl-group.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;

/**
 * DocumentTemplateHook
 *
 * @package TYPO3
 * @subpackage tx_exlpageadvanced
 */
class DocumentTemplateHook {
	private $acceptedDoktypes = array(1, 42, 43, 44, 45);

	/**
	 * Hook on : $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass']
	 *
	 * It allows to get a custom TCA configuration of the way the columns are
	 * displayed ; allowing you to have custom tabs, and custom fields order.
	 */
	public function getMainFields_preProcess() {
		if (GeneralUtility::_GP('in-page-edit-iframe')) {
			$initialColumns = GeneralUtility::_GP('columns');
			$columns = preg_replace('/--div--;[^,]+,/', '', $initialColumns);
			$columns = GeneralUtility::trimExplode(',', $columns);

			foreach($GLOBALS['TCA']['pages']['columns'] as $key => $val) {
				if (!in_array($key, $columns)) {
					unset($GLOBALS['TCA']['pages']['columns'][$key]);
				}
			}

			$GLOBALS['TCA']['pages']['types'][GeneralUtility::_GP('doktype')]['showitem'] = $initialColumns;
		}
	}

	private function getPageEditIframe() {
		// Only the main "Page" module doesn't throw the "M" var.
		if (!GeneralUtility::_GET('M') && !GeneralUtility::_GET('returnUrl') && $this->uid > 0) {
			$page = BackendUtility::getRecord('pages', $this->uid, 'doktype');

			// TODO : non
			if (in_array($page['doktype'], $this->acceptedDoktypes)) {
				$tsConf = BackendUtility::getPagesTSconfig($this->uid);

				if (isset($tsConf['TCEFORM.']['pageHeaderColumns.'][$page['doktype'] . '.']['list'])) {
					$columns = $tsConf['TCEFORM.']['pageHeaderColumns.'][$page['doktype'] . '.']['list'];

					$iframe = '
						<script type="text/javascript">
							function toggleBody() {
								if (document.getElementById("toggle-page-frame").className == "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-collapse") {
									document.getElementById("page-frame").contentDocument.getElementById("typo3-docbody").style.display = "none";
									document.getElementById("toggle-page-frame").className = "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-expand";
									ajaxSwitchPagePanel("NO");
								}
								else {
									document.getElementById("page-frame").contentDocument.getElementById("typo3-docbody").style.display = "block";
									document.getElementById("toggle-page-frame").className = "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-collapse";
									ajaxSwitchPagePanel("YES");
								}

								refreshIframeHeight();

								return false;
							}

							function ajaxSwitchPagePanel(flag) {
								document.getElementById("page-frame").contentWindow.TYPO3.jQuery.ajax({
									async: "true",
									url: "ajax.php",
									type: "GET",

									data: {
										ajaxID: "exl_utilities::ajaxDispatcher",
										request: {
											id:			3,
											function: "TYPO3\\\VersaillesUtilities\\\Hook\\\DocumentTemplateHook->switchPagePanel",
											arguments:		{
												flag: flag
											}
										}
									}
								});
							}

							function refreshIframeHeight() {
								var height = 25;
								if (document.getElementById("toggle-page-frame").className == "t3-icon t3-icon-actions t3-icon-actions-view t3-icon-view-table-collapse") {
									var height = document.getElementById("page-frame").contentDocument.getElementById("typo3-inner-docbody").clientHeight + 100;
								}

								document.getElementById("page-frame").style.height = height + "px";
							}

							document.onreadystatechange = function () {
								var state = document.readyState;
								if (state == "complete") {
									setInterval(function() { refreshIframeHeight(); }, 200);
								}
							}
						</script>
						';

					$toggle = ($GLOBALS['BE_USER']->uc['tx_versaillesutilities_page_panel'] == 'YES') ? 't3-icon-view-table-collapse' : 't3-icon-view-table-expand';

					$iframe .= '
						<div style="position: fixed; width: 100%; z-index: 512;">
							<div class="typo3-docheader-buttons" style="padding-bottom: 5px;">
								<div class="left">
									<span id="toggle-page-frame" class="t3-icon t3-icon-actions t3-icon-actions-view ' . $toggle . '">
										<input type="image" onclick="return toggleBody();" class="c-inputButton" src="clear.gif" title="Ouvrir/Fermer le bloc d\'informations complémentaires">
									</span>
									<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-save">
										<input onclick="document.getElementById(\'page-frame\').contentDocument.getElementsByName(\'_savedok\')[0].click(); return false;" type="image" class="c-inputButton" src="clear.gif" title="Save document">
									</span>
								</div>
							</div>
						</div>

						<div style="">
							<iframe id="page-frame" name="content" style="width: 100%; height: 100%;" frameborder="0" src="../../../alt_doc.php?in-page-edit-iframe=1&edit[pages][' . $this->uid . ']=edit&columns=' . $columns . '&doktype=' . $page['doktype'] . '&noView=0&returnUrl=close.html"></iframe>
						</div>
						';

					$params['moduleBody'] = str_replace('<div id="typo3-inner-docbody">', $iframe . '<div id="typo3-inner-docbody">', $params['moduleBody']);
				}
			}
		}
	}

	/**
	 * Hook on : $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['moduleBodyPostProcess']
	 *
	 * Function called before the backend module area rendering. It allows to
	 * add our own edition panel.
	 *
	 * @param $params array
	 */
	public function moduleBodyPostProcess($params) {
		$this->uid = (int) GeneralUtility::_GET('id');

		if (GeneralUtility::_GET('in-page-edit-iframe')) {
			$replace = array(
				'typo3-docheader-functions',
				'###CSH###',
				'###LANGSELECTOR###',
				'###PAGEPATH###',
				'###PAGEINFO###',
				'###BUTTONLIST_RIGHT###',
			);
			$params['moduleBody'] = str_replace($replace, '', $params['moduleBody']);

			$params['markers']['CONTENT'] = preg_replace('#<h1>((?!</h1>).*)</h1>#', '', $params['markers']['CONTENT']);
			$params['markers']['CONTENT'] = preg_replace('#<div[^>]*class="typo3-TCEforms-recHeaderRow">((?!</div>).*)</div>#', '', $params['markers']['CONTENT']);
			$params['markers']['BUTTONLIST_LEFT'] = preg_replace('#.*(<span[^>]*t3-icon-document-save"[^>]*>((?!</span>).)*</span>).*#', '$1', $params['markers']['BUTTONLIST_LEFT']);

//			$params['markers']['BUTTONLIST_LEFT'] = '<div style="display: none;">' . $params['markers']['BUTTONLIST_LEFT'] . '</div>';
		}
		else {
			$this->getPageEditIframe();
		}
	}

	/**
	 * This function is called via Ajax to save in the current backend user's
	 * configuration whether the panel is opened or not.
	 */
	public function switchPagePanel() {
		$request = GeneralUtility::_GET('request');
		$arguments = $request['arguments'];

		$GLOBALS['BE_USER']->uc['tx_versaillesutilities_page_panel'] = $arguments['flag'];
		$GLOBALS['BE_USER']->overrideUC();
		$GLOBALS['BE_USER']->writeUC();

		echo $GLOBALS['BE_USER']->uc['tx_versaillesutilities_page_panel'];
	}
}