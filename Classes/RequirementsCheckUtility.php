<?php
namespace ImagickImgTeam\Imagickimg;

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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reports\Status;

/**
 * A checker which hooks into the backend module "Reports" checking whether
 * PHP extension imagick is loaded
 */
class RequirementsCheckUtility implements \TYPO3\CMS\Reports\StatusProviderInterface {
	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @return array
	 */
	public function getStatus()
	{
		$reports = array(
			'noImagickExtensionInstalled' => $this->checkIfImagickExtensionIsInstalled()
		);
		return $reports;
	}

	/**
	 * Check whether dbal extension is installed
	 *
	 * @return Status
	 */
	protected function checkIfImagickExtensionIsInstalled()
	{
		$lang = $GLOBALS['LANG'];
		$extKey = 'imagickimg';

		if (extension_loaded('imagick')) {
			$value = $lang->sL('LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:imagick_loaded');
			$message = '';
			$severity = Status::OK;
		} else {
			$value = $lang->sL('LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:no_imagick_title');
			$message = $lang->sL('LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:no_imagick_loaded');
			$severity = Status::ERROR;
		}
		/** @var Status $status */
		$status = GeneralUtility::makeInstance(
			Status::class,
			$lang->sL('LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang.xlf:php_ext_imagick'), 
			$value, $message, $severity);
		return $status;
    }
}
