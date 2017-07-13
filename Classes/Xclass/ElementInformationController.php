<?php
namespace ImagickImgTeam\Imagickimg\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Tomasz Krawczyk <tomasz@typo3.pl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\ProcessedFile;
 
class ElementInformationController extends \TYPO3\CMS\Backend\Controller\ContentElement\ElementInformationController {

	private $extKey = 'imagickimg';

	/**
	 *  @brief Renders file information. This function is used in TYPO3 version 6.x
	 *  
	 *  @param [in] $returnLinkTag Parameter_Description
	 *  @details Details
	 */
	public function renderFileInfo($returnLinkTag) {
		
		if (TYPO3_DLOG) GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		$fileExtension = $this->fileObject->getExtension();
		$code = '<div class="fileInfoContainer">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForFile($fileExtension) . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.file', TRUE) . ':</strong> ' . $this->fileObject->getName() . '&nbsp;&nbsp;' . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.filesize') . ':</strong> ' . GeneralUtility::formatSize($this->fileObject->getSize()) . '</div>';
		$this->content .= $this->doc->section('', $code);
		$this->content .= $this->doc->divider(2);
		
		// If the file was an image...
		// @todo: add this check in the domain model, or in the processing folder
		if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
			// @todo: find a way to make getimagesize part of the t3lib_file object
			$file = $this->fileObject->getForLocalProcessing(FALSE);

			$imgObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$imgObj->init();
			$imgObj->mayScaleUp = 0;
			$imgObj->absPrefix = PATH_site;
			
			$imgInfo = $imgObj->imagickGetDetailedImageInfo($file);
			if (TYPO3_DLOG)
				GeneralUtility::devLog(__METHOD__ . 'Got image info', $this->extKey, -1, $imgInfo);
			
			if (is_array($imgInfo)) {

				$thumbUrl = $this->fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => '150m', 'height' => '150m'))->getPublicUrl(TRUE);
				//$code = '<div class="fileInfoContainer fileDimensions">' . '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.dimensions') . ':</strong> ' . $imgInfo['Basic image properties']['Image dimensions: '] . ' ' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.pixels') . '</div>';
				
				$code = '';
				foreach($imgInfo as $m => $g ) {
					
					if (!empty($g)) {
					
						$code .= '<h2>' . $m . '</h2>';
						$code .= '<table border="1" cellspacing="0" cellpadding="5" class="typo3-dblist" style="font-size: 11px; border-collapse:collapse;">';
					
						foreach($g as $k => $v) {
							$code .= '<tr><td class="t3-col-header" style="width: 170px;"><b>' . $k . '</b></td>';
							$code .= '<td>' . htmlspecialchars(trim($v), ENT_QUOTES | ENT_IGNORE, 'UTF-8') . '&nbsp;</td></tr>';						
						}
					}
					$code .= '</table>';
				}
				$code .= $this->doc->spacer(10);
			}
			
			$code .= '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" alt="' . htmlspecialchars(trim($this->fileObject->getName())) . '" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" /></a></div>';
			$this->content .= $this->doc->section('', $code);
			
		} elseif ($fileExtension == 'ttf') {
			$thumbUrl = $this->fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, array('width' => '530m', 'height' => '600m'))->getPublicUrl(TRUE);
			$thumb = '<br />
				<div align="center">' . $returnLinkTag . '<img src="' . $thumbUrl . '" border="0" title="' . htmlspecialchars(trim($this->fileObject->getName())) . '" alt="" /></a></div>';
			$this->content .= $this->doc->section('', $thumb);
		}
		
		// Traverse the list of fields to display for the record:
		$tableRows = array();
		$showRecordFieldList = $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'];
		$fieldList = GeneralUtility::trimExplode(',', $showRecordFieldList, TRUE);

		foreach ($fieldList as $name) {
			// Ignored fields
			if ($name === 'size') {
				continue;
			}
			if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
				continue;
			}
			$isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $this->table . ':' . $name));
			if ($isExcluded) {
				continue;
			}
			$uid = $this->row['uid'];
			$itemValue = \BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, FALSE, $uid);
			$itemLabel = $GLOBALS['LANG']->sL(\BackendUtility::getItemLabel($this->table, $name), 1);
			$tableRows[] = '
				<tr>
					<td class="t3-col-header">' . $itemLabel . '</td>
					<td>' . htmlspecialchars($itemValue) . '</td>
				</tr>';
		}
		// Create table from the information:
		$tableCode = '
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-showitem" class="t3-table-info">
				' . implode('', $tableRows) . '
			</table>';
		$this->content .= $this->doc->section('', $tableCode);
		// References:
		if ($this->fileObject->isIndexed()) {
			$references = $this->makeRef('_FILE', $this->fileObject);

			if (!empty($references)) {
				$header = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:show_item.php.referencesToThisItem');
				$this->content .= $this->doc->section($header, $references);
			}
		}
	}
	
	/**
	 *  @brief Renders file information as a HTML table. This function is used in TYPO3 version 7.x
	 *  
	 *  @return string containing a HTML table with image information
	 */
	protected function renderPropertiesAsTable() {
		
		$sRes = parent::renderPropertiesAsTable();
		
		$fileExtension = $this->fileObject->getExtension();
		
		if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
						$file = $this->fileObject->getForLocalProcessing(FALSE);

			$imgObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$imgObj->init();
			$imgObj->mayScaleUp = 0;
			$imgObj->absPrefix = PATH_site;
			
			$imgInfo = $imgObj->imagickGetDetailedImageInfo($file);
			if (TYPO3_DLOG)
				GeneralUtility::devLog(__METHOD__ . 'Got image info', $this->extKey, -1, $imgInfo);

			if (is_array($imgInfo)) {
				$sRes .= '<h3>Imagickimg: detailed image information</h3>';
				
				$strSectionInfo = '';
				foreach($imgInfo as $section => $block) {
					$strHead = '<h4>' . $section . '</h4>';
					$strHead .= '<div class="table-fit table-fit-wrap">';
					$strHead .= '	<table class="table table-striped table-hover">';

					$strItem = '';
					if (is_array($block)) {
						$strBlock = '';
						foreach($block as $k => $v) {
							$strBlock .= '<tr><th class="col-nowrap">' . $k . '</th><td>' . $v . '</td></tr>';
						}
						$strItem .= $strBlock;
					}					

					$strFoot = '	</table>';
					$strFoot .= '</div>';
					
					$strSectionInfo .= $strHead . $strItem . $strFoot;
				}
				$sRes .= $strSectionInfo;
			}

		}
		
		return $sRes;
	}

}
