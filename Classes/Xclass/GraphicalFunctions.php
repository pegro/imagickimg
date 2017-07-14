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

/**
 * Contains GraphicalFunctions Xclass object.
 *
 */
class GraphicalFunctions extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions {

	private $NO_IMAGICK = FALSE;

	/** @var $imagick \ImagickImgTeam\Imagickimg\Imaging\ImagickFunctions */
	private $imagick;

	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * Additionally function checks if PHP extension Imagick is loaded.
	 *
	 * @return	void
	 */
	public function init() {

		$this->imagick = GeneralUtility::makeInstance(ImagickFunctions::class);

		$this->NO_IMAGICK = $this->imagick->init($this);

		parent::init();
	}

   /**
     * Gets ImageMagick & Imagick versions.
     *
     * @param   boolean $returnString	if true short string version string will be returned (f.i. im5), else full version array.
     * @return	string|array	Version info
     *
     */
	public function getIMversion($returnString = TRUE) {
		return $this->imagick->getIMversion($returnString);
	}	

	/**
	 * Executes a ImageMagick "convert" on two filenames, $input and $output using $params before them.
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param string $input The relative (to PATH_site) image filepath, input file (read from)
	 * @param string $output The relative (to PATH_site) image filepath, output filename (written to)
	 * @param string $params ImageMagick parameters
	 * @param integer $frame Optional, refers to which frame-number to select in the image. '' or 0
	 * @return string The result of a call to PHP function "exec()
	 */
	public function imageMagickExec($input, $output, $params, $frame = 0) {

		if ($this->NO_IMAGICK) {
			return parent::imageMagickExec($input, $output, $params, $frame);
		}

		return $this->imagick->imageMagickExec($input, $output, $params, $frame);
	}

	/**
	 * Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * Using ImageMagick
	 *
	 * @param	string	$imagefile	The relative (to PATH_site) image filepath
	 * @return	array
	 */	 
	public function imageMagickIdentify($imagefile) {
		
		if ($this->NO_IMAGICK) {
			return parent::imageMagickIdentify($imagefile);
		}

		return $this->imagick->imageMagickIdentify($imagefile);
	}

	/**
	 * Executes an ImageMagick "combine" (or composite in newer times) on four filenames - $input, $overlay and $mask as input files and $output as the output filename (written to)
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param string $input The relative (to PATH_site) image filepath, bottom file
	 * @param string $overlay The relative (to PATH_site) image filepath, overlay file (top)
	 * @param string $mask The relative (to PATH_site) image filepath, the mask file (grayscale)
	 * @param string $output The relative (to PATH_site) image filepath, output filename (written to)
	 * @return string
	 */
	public function combineExec($input, $overlay, $mask, $output) {

		if ($this->NO_IMAGICK) {
			return parent::combineExec($input, $overlay, $mask, $output);
		}

		return $this->imagick->combineExec($input, $overlay, $mask, $output);
	}

	/**
	 * TODO convert to imagick
	 *
	 * Compressing a GIF file if not already LZW compressed.
	 * This function is a workaround for the fact that ImageMagick and/or GD does not compress GIF-files to their minimun size (that is RLE or no compression used)
	 *
	 * The function takes a file-reference, $theFile, and saves it again through GD or ImageMagick in order to compress the file
	 * GIF:
	 * If $type is not set, the compression is done with ImageMagick (provided that $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] is pointing to the path of a lzw-enabled version of 'convert') else with GD (should be RLE-enabled!)
	 * If $type is set to either 'IM' or 'GD' the compression is done with ImageMagick and GD respectively
	 * PNG:
	 * No changes.
	 *
	 * $theFile is expected to be a valid GIF-file!
	 * The function returns a code for the operation.
	 *
	 * @param string $theFile Filepath
	 * @param string $type See description of function
	 * @return string Returns "GD" if GD was used, otherwise "IM" if ImageMagick was used. If nothing done at all, it returns empty string.
	 */
	public static function gifCompress($theFile, $type)
	{
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		if (!$gfxConf['gif_compress'] || strtolower(substr($theFile, -4, 4)) !== '.gif') {
			return '';
		}

		if (($type === 'IM' || !$type) && $gfxConf['processor_enabled'] && $gfxConf['processor_path_lzw']) {
			// Use temporary file to prevent problems with read and write lock on same file on network file systems
			$temporaryName = dirname($theFile) . '/' . md5(uniqid('', true)) . '.gif';
			// Rename could fail, if a simultaneous thread is currently working on the same thing
			if (@rename($theFile, $temporaryName)) {
				/*
				$cmd = CommandUtility::imageMagickCommand('convert', '"' . $temporaryName . '" "' . $theFile . '"', $gfxConf['processor_path_lzw']);
				CommandUtility::exec($cmd);
				*/
				unlink($temporaryName);
			}
			$returnCode = 'IM';
			if (@is_file($theFile)) {
				GeneralUtility::fixPermissions($theFile);
			}
		} elseif (($type === 'GD' || !$type) && $gfxConf['gdlib'] && !$gfxConf['gdlib_png']) {
			$tempImage = imagecreatefromgif($theFile);
			imagegif($tempImage, $theFile);
			imagedestroy($tempImage);
			$returnCode = 'GD';
			if (@is_file($theFile)) {
				GeneralUtility::fixPermissions($theFile);
			}
		} else {
			$returnCode = '';
		}

		return $returnCode;
	}

	/**
	 * TODO convert to imagick
	 *
	 * Returns filename of the png/gif version of the input file (which can be png or gif).
	 * If input file type does not match the wanted output type a conversion is made and temp-filename returned.
	 *
	 * @param string $theFile Filepath of image file
	 * @param bool $output_png If TRUE, then input file is converted to PNG, otherwise to GIF
	 * @return string|NULL If the new image file exists, its filepath is returned
	 */
	public static function readPngGif($theFile, $output_png = false)
	{
		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] || !@is_file($theFile)) {
			return null;
		}

		$ext = strtolower(substr($theFile, -4, 4));
		if ((string)$ext === '.png' && $output_png || (string)$ext === '.gif' && !$output_png) {
			return $theFile;
		}

		if (!@is_dir(PATH_site . 'typo3temp/assets/images/')) {
			GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/assets/images/');
		}
		$newFile = PATH_site . 'typo3temp/assets/images/' . md5($theFile . '|' . filemtime($theFile)) . ($output_png ? '.png' : '.gif');
		/*
		$cmd = CommandUtility::imageMagickCommand(
			'convert', '"' . $theFile . '" "' . $newFile . '"', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path']
		);
		CommandUtility::exec($cmd);
		*/
		if (@is_file($newFile)) {
			GeneralUtility::fixPermissions($newFile);
			return $newFile;
		}
		return null;
	}

	/**
	 * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
	 *
	 * @param	string		$file Image file to reduce
	 * @param	integer		$cols Number of colors to reduce the image to.
	 * @return	string		Reduced file
	 */
	public function IMreduceColors($file, $cols) {

		if ($this->NO_IMAGICK) {
			return parent::IMreduceColors($file, $cols);
		}

		return $this->imagick->IMreduceColors($file, $cols);
	}

	/**
	 * Create thumbnail of given file and size.
	 *
	 * @param $fileIn string filename source
	 * @param $fileOut string filename target
	 * @param $w integer width
	 * @param $h integer height
	 * @return bool
	 */
	public function imagickThumbnailImage($fileIn, $fileOut, $w, $h) {
		if ($this->NO_IMAGICK) {
			return false;
		}

		return $this->imagick->imagickThumbnailImage($fileIn, $fileOut, $w, $h);
	}

}
