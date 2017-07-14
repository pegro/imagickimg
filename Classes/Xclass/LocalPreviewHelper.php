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

use \TYPO3\CMS\Core\Resource;
use \TYPO3\CMS\Core\Utility;

class LocalPreviewHelper extends \TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper {

	private $extKey = 'imagickimg';

	/**
	 * @param $processor Resource\Processing\LocalImageProcessor $processor
	 */
	public function __construct(Resource\Processing\LocalImageProcessor $processor) {

		if (TYPO3_DLOG)
			Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		parent::__construct($processor);
	}
 
 	/**
	 * This method actually does the processing of files locally
	 *
	 * takes the original file (on remote storages this will be fetched from the remote server)
	 * does the IM magic on the local server by creating a temporary typo3temp/ file
	 * copies the typo3temp/ file to the processing folder of the target storage
	 * removes the typo3temp/ file
	 *
	 * @param $task Resource\Processing\TaskInterface $task
	 * @return array
	 */
	public function process(Resource\Processing\TaskInterface $task) {
	
		if (TYPO3_DLOG)
			Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);

		$targetFile = $task->getTargetFile();

			// Merge custom configuration with default configuration
		$configuration = array_merge(array('width' => 64, 'height' => 64), $task->getConfiguration());
		$configuration['width'] = Utility\MathUtility::forceIntegerInRange($configuration['width'], 1, 1000);
		$configuration['height'] = Utility\MathUtility::forceIntegerInRange($configuration['height'], 1, 1000);

		$originalFileName = $targetFile->getOriginalFile()->getForLocalProcessing(FALSE);

			// Create a temporary file in typo3temp/
		if ($targetFile->getOriginalFile()->getExtension() === 'jpg') {
			$targetFileExtension = '.jpg';
		} else {
			$targetFileExtension = '.png';
		}

			// Create the thumb filename in typo3temp/preview_....jpg
		$temporaryFileName = Utility\GeneralUtility::tempnam('preview_') . $targetFileExtension;

		if (TYPO3_DLOG)
			Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($originalFileName, $temporaryFileName, $configuration));

			// Check file extension
		if ($targetFile->getOriginalFile()->getType() != Resource\File::FILETYPE_IMAGE &&
			!Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $targetFile->getOriginalFile()->getExtension())) {
				// Create a default image
			$this->processor->getTemporaryImageWithText($temporaryFileName, 'Not imagefile!', 'No ext!', $targetFile->getOriginalFile()->getName());
		} else {

			if (TYPO3_DLOG)
				Utility\GeneralUtility::devLog(__METHOD__ . ' Create the temporary file', $this->extKey);
		
				// Create the temporary file
			if (TYPO3_DLOG)
				Utility\GeneralUtility::devLog(__METHOD__ . ' executing GraphicalFunctions->imagickThumbnailImage', $this->extKey);
			
			$graphics = Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');
			$graphics->init();
			$graphics->mayScaleUp = 0;
			$graphics->imagickThumbnailImage(
				$originalFileName,
				$temporaryFileName,
				$configuration['width'],
				$configuration['height']
			);
			
			if (!file_exists($temporaryFileName)) {
				if (TYPO3_DLOG)
					Utility\GeneralUtility::devLog(__METHOD__ . ' file: ' . $temporaryFileName . ' doesn\'t exists', $this->extKey);
					// Create a error gif
				$this->processor->getTemporaryImageWithText($temporaryFileName, 'No thumb', 'generated!', $targetFile->getOriginalFile()->getName());
			}
			
		}

		return array(
			'filePath' => $temporaryFileName,
		);
	}

}
