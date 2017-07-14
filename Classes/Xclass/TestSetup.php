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

class TestSetup extends \TYPO3\CMS\Install\Controller\Action\Tool\TestSetup {
	
    /**
     * Determine ImageMagick / GraphicsMagick version
     *
     * @return string Version
     */
    protected function determineImageMagickVersion() {
		$version = 'Unknown';

		try {
			/** @var \ImagickImgTeam\Imagickimg\Xclass\GraphicalFunctions $imageProcessor */
			$imageProcessor = $this->initializeImageProcessor();

			$verArr = $imageProcessor->getIMversion(FALSE);
			$string = $verArr['versionString'];

			list(, $version) = explode('Magick', $string);
			list($version) = explode(' ', trim($version));
			$version = trim($version);
		}
		catch(\ImagickException $e) {
			
			GeneralUtility::sysLog(
				__METHOD__ . ' >> ' . $e->getMessage(),
				$this->extKey,
				GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}

		return $version;
    }	
}
