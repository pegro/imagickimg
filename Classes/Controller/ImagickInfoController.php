<?php
namespace ImagickImgTeam\Imagickimg\Controller;
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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Backend module controller
 *
 * @author	Tomasz Krawczyk <tomasz@typo3.pl>
 */
class ImagickInfoController extends AbstractModuleController
{
    protected function GetImageMagickVersion() {
		$im = new \Imagick();
		$ver = $im->getVersion();
		$im->destroy();
		
		return $ver['versionString'];
	}

	protected function GetPHPImagickVersion() {
		return \Imagick::IMAGICK_EXTVER;
	}	

    public function indexAction() {
		if (!$this->imagickLoaded) {
			$this->addFlashMessage(
				LocalizationUtility::translate('no_imagick_loaded', $this->extKey),
				'',
				\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
				FALSE
			);
			return;
		}

		$versions = array(
			'im' => $this->GetImageMagickVersion(),
			'imagick' => $this->GetPHPImagickVersion()
		);
		$this->view->assign('versions', $versions);
    }

}