<?php

namespace ImagickImgTeam\Imagickimg\Xclass;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Tomasz Krawczyk <tomasz@typo3.pl>
 *  (c) 2017 Peter Große <pegro@friiks.de>
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

use ImagickImgTeam\Imagickimg\Imaging\ImagickFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PreviewProcessing extends \TYPO3\CMS\Core\Resource\OnlineMedia\Processing\PreviewProcessing {

	/**
	 * TODO convert to imagick
	 *
	 * @param string $originalFileName
	 * @param string $temporaryFileName
	 * @param array $configuration
	 */
	protected function resizeImage($originalFileName, $temporaryFileName, $configuration)
	{
        if (TYPO3_DLOG)
            GeneralUtility::devLog(__METHOD__, ImagickFunctions::$extKey);

        // Create the temporary file
		if (empty($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'])) {
			return;
		}

		if (file_exists($originalFileName)) {
			$arguments = \TYPO3\CMS\Core\Utility\CommandUtility::escapeShellArguments([
				'width' => $configuration['width'],
				'height' => $configuration['height'],
				'originalFileName' => $originalFileName,
				'temporaryFileName' => $temporaryFileName,
			]);
			$parameters = '-sample ' . $arguments['width'] . 'x' . $arguments['height'] . ' '
				. $arguments['originalFileName'] . '[0] ' . $arguments['temporaryFileName'];

			/*$cmd = CommandUtility::imageMagickCommand('convert', $parameters) . ' 2>&1';
			CommandUtility::exec($cmd);
			*/
		}

		if (!file_exists($temporaryFileName)) {
			// Create a error image
			$graphicalFunctions = $this->getGraphicalFunctionsObject();
			$graphicalFunctions->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', basename($originalFileName));
		}
	}
}