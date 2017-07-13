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

class ThumbnailView extends \TYPO3\CMS\Backend\View\ThumbnailView {

	private $extKey = 'imagickimg';

	/**
	 * Create the thumbnail
	 * Will exit before return if all is well.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {

		if (TYPO3_DLOG) \TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey);
		
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		if (TYPO3_DLOG)
			\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, 0, array($this->image, $gfxConf));

		// If file exists, we make a thumbnail of the file.
		if (is_object($this->image)) {
			// Check file extension:
			if ($this->image->getExtension() == 'ttf') {
				// Make font preview... (will not return)
				$this->fontGif($this->image);
			} elseif ($this->image->getType() != \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $this->image->getExtension())) {
				$this->errorGif('Not imagefile!', 'No ext!', $this->image->getName());
			}
			// ... so we passed the extension test meaning that we are going to make a thumbnail here:
			// default
			if (!$this->size) {
				$this->size = $this->sizeDefault;
			}
			// I added extra check, so that the size input option could not be fooled to pass other values.
			// That means the value is exploded, evaluated to an integer and the imploded to [value]x[value].
			// Furthermore you can specify: size=340 and it'll be translated to 340x340.
			// explodes the input size (and if no "x" is found this will add size again so it is the same for both dimensions)
			$sizeParts = explode('x', $this->size . 'x' . $this->size);
			// Cleaning it up, only two parameters now.
			$sizeParts = array(\TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($sizeParts[0], 1, 1000), \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($sizeParts[1], 1, 1000));
			// Imploding the cleaned size-value back to the internal variable
			$this->size = implode('x', $sizeParts);
			// Getting max value
			$sizeMax = max($sizeParts);
			// Init
			$outpath = PATH_site . $this->outdir;
			// Should be - ? 'png' : 'gif' - , but doesn't work (ImageMagick prob.?)
			// René: png work for me
			$thmMode = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails_png'], 0);
			$outext = ($this->image->getExtension() != 'jpg' || $thmMode & 2) ? ($thmMode & 1 ? 'png' : 'gif') : 'jpg';
			$outfile = 'tmb_' . substr(md5(($this->image->getName() . $this->mtime . $this->size)), 0, 10) . '.' . $outext;
			$this->output = $outpath . $outfile;

			if (TYPO3_DLOG)
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(__METHOD__, $this->extKey, -1, array($thmMode, $outext, $outfile, $outpath);
			
			// If thumbnail does not exist, we generate it
			if (!file_exists($this->output)) {

				$originalFileName = $this->image->getForLocalProcessing(FALSE);
			
				$graphics = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions');				
				$graphics->init();
				$graphics->mayScaleUp = 0; 
				$graphics->imagickThumbnailImage(
					$originalFileName,
					$this->output,
					$sizeParts[0],
					$sizeParts[1]
				);

			}
			// The thumbnail is read and output to the browser
			if ($fd = @fopen($this->output, 'rb')) {
				$fileModificationTime = filemtime($this->output);
				header('Content-type: image/' . $outext);
				header('Last-Modified: ' . date('r', $fileModificationTime));
				header('Etag: ' . md5($this->output) . '-' . $fileModificationTime);
				// Expiration time is choosen arbitrary to 1 month
				header('Expires: ' . date('r', ($fileModificationTime + 30 * 24 * 60 * 60)));
				fpassthru($fd);
				fclose($fd);
			} else {
				$this->errorGif('Read problem!', '', $this->output);
			}

		} else {
			$this->errorGif('No valid', 'inputfile!', basename($this->image));
		}
	}

}
