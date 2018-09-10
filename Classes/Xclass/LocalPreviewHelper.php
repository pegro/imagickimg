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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Processing\LocalImageProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocalPreviewHelper extends \TYPO3\CMS\Core\Resource\Processing\LocalPreviewHelper {

	/**
	 * @param $processor LocalImageProcessor $processor
	 */
	public function __construct(LocalImageProcessor $processor) {

		if (TYPO3_DLOG)
			GeneralUtility::devLog(__METHOD__, ImagickFunctions::$extKey);

		parent::__construct($processor);
	}
 
    /**
     * Generates a preview for a file
     *
     * @param File $file The source file
     * @param array $configuration Processing configuration
     * @param string $targetFilePath Output file path
     * @return array
     */
    protected function generatePreviewFromFile(File $file, array $configuration, $targetFilePath)
    {
        if (TYPO3_DLOG)
            GeneralUtility::devLog(__METHOD__, ImagickFunctions::$extKey);

        // Check file extension
        if ($file->getType() !== File::FILETYPE_IMAGE
            && !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $file->getExtension())
        ) {
            // Create a default image
            $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
            $graphicalFunctions->getTemporaryImageWithText(
                $targetFilePath,
                'Not imagefile!',
                'No ext!',
                $file->getName()
            );
            return [
                'filePath' => $targetFilePath,
            ];
        }

        $originalFileName = $file->getForLocalProcessing(false);

        if (TYPO3_DLOG)
            GeneralUtility::devLog(__METHOD__, ImagickFunctions::$extKey, 0, array($originalFileName, $targetFilePath, $configuration));

        if ($file->getExtension() === 'svg') {
            /** @var $gifBuilder \TYPO3\CMS\Frontend\Imaging\GifBuilder */
            $gifBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Imaging\GifBuilder::class);
            $gifBuilder->init();
            $gifBuilder->absPrefix = PATH_site;
            $info = $gifBuilder->getImageDimensions($originalFileName);
            $newInfo = $gifBuilder->getImageScale($info, $configuration['width'], $configuration['height'], []);
            $result = [
                'width' => $newInfo[0],
                'height' => $newInfo[1],
                'filePath' => '' // no file = use original
            ];
        } else {
            // Create the temporary file
            if (TYPO3_DLOG)
                GeneralUtility::devLog(__METHOD__ . ' executing GraphicalFunctions->imagickThumbnailImage', ImagickFunctions::$extKey);

            $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
            $graphicalFunctions->init();
            $graphicalFunctions->mayScaleUp = 0;
            $graphicalFunctions->imagickThumbnailImage(
                $originalFileName,
                $targetFilePath,
                $configuration['width'],
                $configuration['height']
            );

            if (!file_exists($targetFilePath)) {
                // Create an error gif
                $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
                $graphicalFunctions->getTemporaryImageWithText(
                    $targetFilePath,
                    'No thumb',
                    'generated!',
                    $file->getName()
                );
            }
            $result = [
                'filePath' => $targetFilePath,
            ];
        }

        return $result;
    }

}
