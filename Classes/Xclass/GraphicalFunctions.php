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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains GraphicalFunctions Xclass object.
 *
 */
class GraphicalFunctions extends \TYPO3\CMS\Core\Imaging\GraphicalFunctions {

	private $NO_IMAGICK = FALSE;
	private $extKey = 'imagickimg';
	private $imagick_version = 'Unknown';
	private $im_version = 'Unknown';
	private $quantumRange;
	private $gfxConf;
	//private $transparentFormats = array('gif', 'png', 'bmp', 'tiff');
	private $debug = FALSE;
	/** @var $logger \TYPO3\CMS\Core\Log\Logger */
	private $logger;

	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * Additionally function checks if PHP extension Imagick is loaded.
	 *
	 * @return	void
	 */
	public function init() {

		$this->debug = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick_debug'];

		if ($this->debug) {
			$this->logger = GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger(__CLASS__);
			$this->logger->debug(__METHOD__ . ' OK');
		}

		if (!extension_loaded('imagick')) {
			$this->NO_IMAGICK = TRUE;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 0;

			$sMsg = 'PHP extension Imagick is not loaded. Extension Imagickimg is deactivated.';			
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		} else {

			$this->NO_IMAGICK = FALSE;
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 1;

			// Get IM version and overwrite user settings
			$ver = $this->getIMversion(TRUE);
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] = $ver;

			if ($ver == 'im6') {				
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 0;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = 1;
			}
			else {
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_no_effects'] = 1;
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects'] = 0;
			}

			$this->getQuantumRangeLong();
			$this->gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		}

		parent::init();
	}

	private function getIMVersionNumber($strVersion) {
		
		$strRes = $strVersion;
		$p = stripos($strVersion, '-');
		if ($p !== FALSE) {
			$strRes = substr($strVersion, 0, $p);
		}
		
		return $strRes;
	}

   /**
     * Gets ImageMagick & Imagick versions.
     *
     * @param   boolean $returnString	if true short string version string will be returned (f.i. im5), else full version array.
     * @return	string|array	Version info
     *
     */
	public function getIMversion($returnString = TRUE) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return '';

		$im_ver = '';
		try {
			$im = new \Imagick();
			$a = $im->getVersion();
			$im->destroy();
			$this->imagick_version = \Imagick::IMAGICK_EXTVER;

			// $a['versionString'] is string 'ImageMagick 6.7.9-1 2012-08-21 Q8 http://www.imagemagick.org' (length=60)
			if (is_array($a)) {
				// Add Imagick version info
				$a['versionImagick'] = 'Imagick ' . $this->imagick_version;
				
				$v = GeneralUtility::trimExplode(' ', $a['versionString']);
				if (count($v) >= 1) {
					$this->im_version = $this->getIMVersionNumber($v[1]);
					$a = explode('.', $v[1]);
					if (count($a) >= 2) {
						$im_ver = 'im' . $a[0];
					}
				}
			}

			if ($this->debug) $this->logger->debug('Versions', array(
				'IM' => $this->im_version,
				'Imagick' => $this->imagick_version
			));

			if (!$returnString) {
				$im_ver = $a;
			}
		}
		catch(\ImagickException $e) {

			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}				
		}

		return $im_ver;
	}	

	private function getQuantumRangeLong() {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;

		try {
			$newIm = new \Imagick();

			if (version_compare($this->imagick_version, '3.3.0', '>=')) {
				$this->quantumRange = $newIm->getQuantum();
			} else {
				$qrArr = $newIm->getQuantumRange();
				if (is_array($qrArr)) {
					$this->quantumRange = intval($qrArr['quantumRangeLong']);
				} else {
					$this->quantumRange = 1;
				}
			}

			$newIm->destroy();
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
	}	

	/***********************************
	 *
	 * Scaling, Dimensions of images
	 *
	 ***********************************/
	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param string $imagefile The image filepath
	 * @param string $newExt New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param string $w Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param string $h Height. See $w
	 * @param string $params Additional ImageMagick parameters.
	 * @param string $frame Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param array $options An array with options passed to getImageScale (see this function).
	 * @param bool $mustCreate If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @return array|null [0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	public function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = [], $mustCreate = false) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate));

		if ($this->NO_IMAGICK) {
			return parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);
		}

		if ($info = $this->getImageDimensions($imagefile)) {
			$newExt = strtolower(trim($newExt));
			// If no extension is given the original extension is used
			if (!$newExt) {
				$newExt = $info[2];
			}
			if ($newExt == 'web') {
				if (GeneralUtility::inList($this->webImageExt, $info[2])) {
					$newExt = $info[2];
				} else {
					$newExt = $this->gif_or_jpg($info[2], $info[0], $info[1]);
					if (!$params) {
						$params = $this->cmds[$newExt];
					}
				}
			}
			if (GeneralUtility::inList($this->imageFileExt, $newExt)) {
				/* // unused
				if (strstr($w . $h, 'm')) {
					$max = 1;
				} else {
					$max = 0;
				}
				*/
				$data = $this->getImageScale($info, $w, $h, $options);
				$w = $data['origW'];
				$h = $data['origH'];
				// If no conversion should be performed
				// this flag is TRUE if the width / height does NOT dictate
				// the image to be scaled!! (that is if no width / height is
				// given or if the destination w/h matches the original image
				// dimensions or if the option to not scale the image is set)
				$noScale = !$w && !$h || $data[0] == $info[0] && $data[1] == $info[1] || !empty($options['noScale']); 
				if ($noScale && !$data['crs'] && !$params && !$frame && $newExt == $info[2] && !$mustCreate) {
					// Set the new width and height before returning,
					// if the noScale option is set
					if (!empty($options['noScale'])) {
						$info[0] = $data[0];
						$info[1] = $data[1];
					}
					$info[3] = $imagefile;
					return $info;
				}
				$info[0] = $data[0];
				$info[1] = $data[1];
				$frame = !$this->addFrameSelection ? '' : intval($frame);
				if (!$params) {
					$params = $this->cmds[$newExt];
				}
				// Cropscaling:
				if ($data['crs']) {
					if (!$data['origW']) {
						$data['origW'] = $data[0];
					}
					if (!$data['origH']) {
						$data['origH'] = $data[1];
					}
					$offsetX = (int)(($data[0] - $data['origW']) * ($data['cropH'] + 100) / 200);
					$offsetY = (int)(($data[1] - $data['origH']) * ($data['cropV'] + 100) / 200); 
					$params .= ' -crop ' . $data['origW'] . 'x' . $data['origH'] . '+' . $offsetX . '+' . $offsetY . '! ';
				}
				$command = $this->scalecmd . ' ' . $info[0] . 'x' . $info[1] . '! ' . $params . ' ';
				$cropscale = $data['crs'] ? 'crs-V' . $data['cropV'] . 'H' . $data['cropH'] : '';
				if ($this->alternativeOutputKey) {
					$theOutputName = GeneralUtility::shortMD5($command . $cropscale . basename($imagefile) . $this->alternativeOutputKey . '[' . $frame . ']');
				} else {
					$theOutputName = GeneralUtility::shortMD5($command . $cropscale . $imagefile . filemtime($imagefile) . '[' . $frame . ']');
				}
				if ($this->imageMagickConvert_forceFileNameBody) {
					$theOutputName = $this->imageMagickConvert_forceFileNameBody;
					$this->imageMagickConvert_forceFileNameBody = '';
				}
				// Making the temporary filename:
				//$this->createTempSubDir('pics/');
				//$output = $this->absPrefix . $this->tempPath . 'pics/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;				
				GeneralUtility::mkdir_deep(PATH_site . 'typo3temp/assets/images/');
				$output = $this->absPrefix . 'typo3temp/assets/images/' . $this->filenamePrefix . $theOutputName . '.' . $newExt;

				if (!GeneralUtility::isAbsPath($imagefile)) {
					$imagefile = GeneralUtility::getFileAbsFileName($imagefile, FALSE);
				}

				if (!GeneralUtility::isAbsPath($output)) {
					$output = GeneralUtility::getFileAbsFileName($output, FALSE);
				}
				
				if ($this->dontCheckForExistingTempFile || !file_exists($output)) {

					if ($this->debug) $this->logger->debug(__METHOD__ . ' Conversion', array($imagefile, $output));

					try {
						$newIm = new \Imagick($imagefile);
						$newIm->resizeImage($info[0], $info[1], $this->gfxConf['windowing_filter'], 1);

						$newIm->writeImage($output);
						$newIm->destroy();
						
						// apply additional params (f.e. effects, compression)
						if ($params) {
							$this->applyImagickEffect($output, $params);
						}
						// Optimize image
						$this->imagickOptimize($output);
						GeneralUtility::fixPermissions($output);
					}
					catch(\ImagickException $e) {
						
						$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
						if ($this->debug) {
							$this->logger->error($sMsg);
						} else {
							GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
						}
					}
				}
				if (file_exists($output))	{
					$info[3] = $output;
					$info[2] = $newExt;
						// params could realisticly change some imagedata!
					if ($params) {
						$info=$this->getImageDimensions($info[3]);
					}
					if($this->debug) $this->logger->debug(__METHOD__  . ' Done', array($info));
					return $info;
				}
			}
		}
		return null;
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
		
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($input, $output, $params, $frame));

		if ($this->NO_IMAGICK) {
			return parent::imageMagickExec($input, $output, $params, $frame);
		}

		$ret = '';

		if (!GeneralUtility::isAbsPath($input)) {
			$fileInput = GeneralUtility::getFileAbsFileName($input, FALSE);
		} else {
			$fileInput = $input;
		}

		if (!GeneralUtility::isAbsPath($output)) {
			$fileOutput = GeneralUtility::getFileAbsFileName($output, FALSE);
		} else  {
			$fileOutput = $output;
		}

		try {	
			$newIm = new \Imagick($fileInput);

			// If addFrameSelection is set in the Install Tool, a frame number is added to
			// select a specific page of the image (by default this will be the first page)
			if($this->addFrameSelection) {
				$newIm->setIteratorIndex($frame);
			}
		
			$newIm->writeImage($fileOutput);
			$newIm->destroy();
			
			// apply additional params (f.e. effects, compression)
			if ($params) {
				$this->applyImagickEffect($fileOutput, $params);
			}
			
			// Optimize image
			$this->imagickOptimize($fileOutput);
			GeneralUtility::fixPermissions($fileOutput);
			
			$ret = '1';
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
		return $ret;
	}

	/**
	 * Returns an array where [0]/[1] is w/h, [2] is extension and [3] is the filename.
	 * Using ImageMagick
	 *
	 * @param	string	$imagefile	The relative (to PATH_site) image filepath
	 * @return	array
	 */	 
	public function imageMagickIdentify($imagefile) {
		
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($imagefile));

		if ($this->NO_IMAGICK) {
			return parent::imageMagickIdentify($imagefile);
		}

		// BE uses stdGraphics and absolute paths.
		if (!GeneralUtility::isAbsPath($imagefile)) {
			$file = GeneralUtility::getFileAbsFileName($imagefile, FALSE);
		} else {
			$file = $imagefile;
		}
		$arRes = array();
		
		try {
			$newIm = new \Imagick($file);
			// The $im->getImageGeometry() is faster than $im->identifyImage(false).
			$idArr = $newIm->identifyImage(false);

			$arRes[0] = $idArr['geometry']['width'];
			$arRes[1] = $idArr['geometry']['height'];
			$arRes[2] = strtolower(pathinfo($idArr['imageName'], PATHINFO_EXTENSION));
			$arRes[3] = $imagefile;		

			$newIm->destroy();
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
		return $arRes;
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

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($input, $overlay, $mask, $output));
	
		if ($this->NO_IMAGICK) {
			return parent::combineExec($input, $overlay, $mask, $output);
		}

		if (!GeneralUtility::isAbsPath($input)) {
			$fileInput = GeneralUtility::getFileAbsFileName($input, FALSE);
		} else {
			$fileInput = $input;
		}

		if (!GeneralUtility::isAbsPath($overlay)) {
			$fileOver = GeneralUtility::getFileAbsFileName($overlay, FALSE);
		} else {
			$fileOver = $overlay;
		}
			
		if (!GeneralUtility::isAbsPath($mask)) {
			$fileMask = GeneralUtility::getFileAbsFileName($mask, FALSE);
		} else {
			$fileMask = $mask;
		}

		if (!GeneralUtility::isAbsPath($output)) {
			$fileOutput = GeneralUtility::getFileAbsFileName($output, FALSE);
		} else  {
			$fileOutput = $output;
		}
		
		try {
			$baseObj = new \Imagick();
			$baseObj->readImage($fileInput);
			
			$overObj = new \Imagick();
			$overObj->readImage($fileOver);

			$maskObj = new \Imagick();
			$maskObj->readImage($fileMask);
			
			// get input image dimensions
			$geo = $baseObj->getImageGeometry();
			$w = $geo['width'];
			$h = $geo['height'];
			
			// resize mask and overlay
			$maskObj->resizeImage($w, $h, \Imagick::FILTER_LANCZOS, 1);
			$overObj->resizeImage($w, $h, \Imagick::FILTER_LANCZOS, 1);
			
			// Step 1
			$maskObj->setImageColorspace(\Imagick::COLORSPACE_GRAY); // IM >= 6.5.7
			$maskObj->setImageMatte(FALSE); // IM >= 6.2.9

			// Step 2
			$baseObj->compositeImage($maskObj, \Imagick::COMPOSITE_SCREEN, 0, 0); // COMPOSITE_SCREEN
			$maskObj->negateImage(1);
			
			if ($baseObj->getImageFormat() == 'GIF') {
				$overObj->compositeImage($maskObj, \Imagick::COMPOSITE_SCREEN, 0, 0); // COMPOSITE_SCREEN
			}
			$baseObj->compositeImage($overObj, \Imagick::COMPOSITE_MULTIPLY, 0, 0); //COMPOSITE_MULTIPLY
			$baseObj->setImageMatte(FALSE); // IM >= 6.2.9

			$baseObj->writeImage($fileOutput);

			$maskObj->destroy();
			$overObj->destroy();
			$baseObj->destroy();

				// Optimize image
			$this->imagickOptimize($fileOutput);
			GeneralUtility::fixPermissions($output);
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
		
		return '';
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
     * Compresses given image.
     *
	 * @param	string	$imageFile	file name
	 * @param	int		$imageQuality quality
	 * @return	void
     */
	private function imagickQuality($imageFile, $imageQuality) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($imageFile, $imageQuality));
	
		if ($this->NO_IMAGICK) return;
		
		if (!GeneralUtility::isAbsPath($imageFile)) {
			$file = GeneralUtility::getFileAbsFileName($imageFile, FALSE);
		} else {
			$file = $imageFile;
		}

		try {
			$im = new \Imagick($file);

			$fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if (strtoupper($fileExt) == 'GIF') {
				$im->optimizeImageLayers();
			}
			$this->imagickCompressObject($im, $imageQuality);
			
			$im->writeImage($file);
			$im->destroy();
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
	}


    /**
     * Compresses given image.
     *
	 * @param   \Imagick		$imageObj Imagick object
     * @param   int $imageQuality image quality
	 * @return	void
     */
	private function imagickCompressObject(&$imageObj, $imageQuality = 0) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;
		
		$imgExt = strtolower($imageObj->getImageFormat());		
		
		switch($imgExt) {			
			case 'gif':
				if (($imageQuality == 100) || ($this->jpegQuality == 100)) {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_RLE);
				} else {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_LZW);
				}
				break;
			
			case 'jpg':
			case 'jpeg':
				if (($imageQuality == 100) || ($this->jpegQuality == 100)) {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_LOSSLESSJPEG);
				} else {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_JPEG);
				}					
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;

			case 'png':
				$imageObj->setImageCompression(\Imagick::COMPRESSION_ZIP);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;
			
			case 'tif':
			case 'tiff':
				if (($imageQuality == 100) || ($this->jpegQuality == 100)) {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_LOSSLESSJPEG);
				} else {
					$imageObj->setImageCompression(\Imagick::COMPRESSION_LZW);
				}
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;

			case 'tga':
				$imageObj->setImageCompression(\Imagick::COMPRESSION_RLE);
				$imageObj->setImageCompressionQuality(($imageQuality == 0) ? $this->jpegQuality : $imageQuality);
				break;
		}
	}


    /**
     * Removes profiles and comments from the image.
     *
	 * @param	\Imagick	$imageObj	Imagick object
	 * @return	void
     */
	private function imagickRemoveProfile(&$imageObj) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;

		if ($this->gfxConf['im_useStripProfileByDefault']) {
		
			$profile = $this->gfxConf['im_stripProfileCommand'];
			if (substr($profile, 0, 1) == '+') {			
					// remove profiles
				if ( $this->gfxConf['im_stripProfileCommand'] == '+profile \'*\'') {
						// remove all profiles and comments
					$imageObj->stripImage();
				}
			}
		}
	}

    /**
     * Optimizes image resolution.
     *
	 * @param	\Imagick	$imageObj	Imagick object
	 * @return	void
     */
	private function imagickOptimizeResolution(&$imageObj) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;

		$imgDPI = intval($this->gfxConf['imagesDPI']);

		if ($imgDPI > 0) {
			$imageObj->setImageResolution($imgDPI, $imgDPI);
		}
	}
	
    /**
     * Executes all optimization methods on the image. Execute it just before storing image to disk.
     * 
     * @param string	$imageFile	image file
	 * @return	void
     */
	private function imagickOptimize($imageFile) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($imageFile));

		if ($this->NO_IMAGICK) return;

		if (!GeneralUtility::isAbsPath($imageFile)) {
			$file = GeneralUtility::getFileAbsFileName($imageFile, FALSE);
		} else {
			$file = $imageFile;
		}

		try {
			$im = new \Imagick($file);

			$im->optimizeImageLayers();
			$this->imagickOptimizeObject($im);
			
			$im->writeImage($file);
			$im->destroy();
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}
	}

	/**
	 * @param \Imagick $imObject
	 */
	private function imagickOptimizeObject(&$imObject) {
		
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;

		$imObject->optimizeImageLayers();

		$this->imagickRemoveProfile($imObject);
		$this->imagickOptimizeResolution($imObject);
		$this->imagickCompressObject($imObject);
	}


	private function imagickSetColorspace($file, $colorSpace) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $colorSpace));

		if ($this->NO_IMAGICK) return false;

		if (!GeneralUtility::isAbsPath($file)) {
			$fileResult = GeneralUtility::getFileAbsFileName($file, FALSE);
		} else {
			$fileResult = $file;
		}

		try {
			$newIm = new \Imagick();
			$newIm->readImage($fileResult);

			switch(strtoupper($colorSpace)) {
/*
				case 'GRAY':
					
					$newIm->setImageColorspace(\Imagick::COLORSPACE_GRAY); // IM >= 6.5.7
					
					if ($this->debug) $this->logger->notice(__METHOD__  . ' Does this work ?!?!');
					/ *
					$newIm->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
					if (version_compare($this->im_version, '3.0.0', '>=')) {
						$newIm->transformImageColorspace(\Imagick::COLORSPACE_GRAY);
					}* /
					break;
*/
				case 'RGB':
					if (version_compare($this->imagick_version, '3.0.0', '>=')) {
						$newIm->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
					} else {
						$newIm->setImageColorspace(\Imagick::COLORSPACE_SRGB);
					}

					/* http://www.imagemagick.org/script/color-management.php
					$cs = $newIm->getColorspace();
					
					if (($cs != \Imagick::COLORSPACE_RGB) || ($cs != \Imagick::COLORSPACE_SRGB)) {
						
					}

					if ((version_compare($this->im_version, '6.7', '>=') && version_compare($this->im_version, '6.7.5-5', '>=')) ||
						(version_compare($this->im_version, '6.8', '>=') && version_compare($this->im_version, '6.8.0-3', '>=')))
					{
						if (version_compare($this->imagick_version, '3.0.0', '>=')) {
							$newIm->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
						} else {
							$newIm->setImageColorspace(\Imagick::COLORSPACE_SRGB);
						}
					} else {
						$newIm->setImageColorspace(\Imagick::COLORSPACE_RGB);
					}*/
					break;
			}
		
			$newIm->writeImage($fileResult);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}		
	}

	/**
	 * Reduce colors in image using IM and create a palette based image if possible (<=256 colors)
	 *
	 * @param	string		$file Image file to reduce
	 * @param	integer		$cols Number of colors to reduce the image to.
	 * @return	string		Reduced file
	 */
	public function IMreduceColors($file, $cols) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $cols));

		if ($this->NO_IMAGICK) {
			return parent::IMreduceColors($file, $cols);
		}

		$fI = GeneralUtility::split_fileref($file);
		$ext = strtolower($fI['fileext']);
		$result = $this->randomName() . '.' . $ext;
		$reduce = MathUtility::forceIntegerInRange($cols, 0, ($ext == 'gif' ? 256 : $this->truecolorColors), 0);
		if ($reduce > 0) {

			if (!GeneralUtility::isAbsPath($file)) {
				$fileInput = GeneralUtility::getFileAbsFileName($file, FALSE);
			} else {
				$fileInput = $file;
			}

			if (!GeneralUtility::isAbsPath($result)) {
				$fileResult = GeneralUtility::getFileAbsFileName($result, FALSE);
			} else {
				$fileResult = $result;
			}

			if ($this->debug) {
				$this->logger->debug('Params ', array($fileInput, $fileResult, $reduce));
			}

			try {
				$newIm = new \Imagick($fileInput);
			
				if ($reduce <= 256) {
					$newIm->setType(\Imagick::IMGTYPE_PALETTE);
				}
				if (($ext == 'png') && ($reduce <= 256)) {
					$newIm->setImageDepth(8);
					$newIm->setImageFormat('PNG8');
				}			
				
				// Reduce the amount of colors
				$newIm->quantizeImage($reduce, \Imagick::COLORSPACE_RGB, 0, false, false);
				
				$newIm->writeImage($fileResult);
				$newIm->destroy();
				
				GeneralUtility::fixPermissions($fileResult);
				
				return $result;	
			}
			catch(\ImagickException $e) {
				
				$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
				if ($this->debug) {
					$this->logger->error($sMsg);
				} else {
					GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
				}
			}
		}
		return '';
	}

    /**
     * Main function applying Imagick effects
     *
	 * @param	string		$file image file
	 * @param	string		$command The ImageMagick parameters. Like effects, scaling etc.
	 * @return	void
     */
	private function applyImagickEffect($file, $command) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $command));

		if ($this->NO_IMAGICK || $this->NO_IM_EFFECTS) return;

		$command = strtolower(trim($command));
		$command = str_ireplace('-', '', $command);		
		$elems = GeneralUtility::trimExplode(' ', $command, true);
		$nElems = count($elems);

		if ($this->debug) $this->logger->debug('Elems', array($file, $elems));

			// Here we're trying to identify ImageMagick parameters
			// Image compression see tslib_cObj->image_compression
			// Image effects see tslib_cObj->image_effects

		if ($nElems == 1) {

			switch($elems[0]) {
				// effects
				case 'normalize':
					$this->imagickNormalize($file);
					break;

				case 'contrast':
					$this->imagickContrast($file);
					break;
			}
		}
		elseif ($nElems == 2) {

			switch($elems[0]) {
				// effects
				case 'rotate':
					$this->imagickRotate($file, $elems[1]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray') {
						$this->imagickGray($file);
					} else {
						$this->imagickSetColorspace($file, $elems[1]);
					}
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[1]);
					break;
					
				case 'gamma':	// brighter, darker
					$this->imagickGamma($file, $elems[1]);
					break;
				
				case '@sepia':
					$this->imagickSepia($file, floatval($elems[1]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, intval($elems[1]));
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, floatval($elems[1]));
					break;

				// compression
				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[1]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}					
					break;

				case 'quality':
					$this->imagickQuality($file, intval($elems[1]));
					break;

				case 'crop':
					$this->imagickCrop($file, $elems[1]);
					break;
			}
		}
		elseif ($nElems == 3) {
				
				// effects without parameters
			switch($elems[0]) {
			
				case 'normalize':
					$this->imagickNormalize($file);
					break;

				case 'contrast':
					$this->imagickContrast($file);
					break;
			}
				// compression 
			switch($elems[1]) {

				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[2]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}					
					break;

				case 'quality':
					$this->imagickQuality($file, intval($elems[2]));
					break;
			}

		}
		elseif ($nElems == 4) {

			// effect
			switch($elems[0]) {

				case 'rotate':
					$this->imagickRotate($file, $elems[1]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray')
						$this->imagickGray($file);
					else
						$this->imagickSetColorspace($file, $elems[1]);
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[1]);
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, intval($elems[1]));
					break;
				
				case '@sepia':
					$this->imagickSepia($file, floatval($elems[1]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, intval($elems[1]));
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, floatval($elems[1]));
					break;
			}
			
			// compression
			switch($elems[2]) {

				case 'colors':
					$reduced = $this->IMreduceColors($file, intval($elems[3]));
					if ($reduced) {
						@copy($reduced, $file);
						@unlink($reduced);
					}					
					break;

				case 'quality':
					$this->imagickQuality($file, intval($elems[3]));
					break;
			}
		}
		elseif ($nElems == 6) {

			// colorspace
			switch($elems[0]) {
				case 'colorspace':
					if ($elems[1] == 'gray') {
						$this->imagickGray($file);
					} else {
						$this->imagickSetColorspace($file, $elems[1]);
					}
					break;
			}

			// quality
			switch($elems[2]) {
				case 'quality':
					$this->imagickQuality($file, intval($elems[3]));
					break;
			}

			// effect
			switch($elems[4]) {

				case 'rotate':
					$this->imagickRotate($file, $elems[5]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray') {
						$this->imagickGray($file);
					} else {
						$this->imagickSetColorspace($file, $elems[1]);
					}
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[5]);
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, intval($elems[5]));
					break;

				case '@sepia':
					$this->imagickSepia($file, floatval($elems[5]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, intval($elems[5]));
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, floatval($elems[5]));
					break;
			}
		}
		elseif ($nElems == 8) {

			// colorspace
			switch($elems[0]) {
				case 'colorspace':
					if ($elems[1] == 'gray') {
						$this->imagickGray($file);
					} else {
						$this->imagickSetColorspace($file, $elems[1]);
					}
					break;
			}

			// quality
			switch($elems[2]) {
				case 'quality':
					$this->imagickQuality($file, intval($elems[3]));
					break;
			}

			// effect
			switch($elems[4]) {

				case 'rotate':
					$this->imagickRotate($file, $elems[5]);
					break;

				case 'colorspace':
					if ($elems[1] == 'gray') {
						$this->imagickGray($file);
					} else {
						$this->imagickSetColorspace($file, $elems[1]);
					}
					break;

				case 'sharpen':
					$this->imagickSharpen($file, $elems[5]);
					break;
					// brighter, darker
				case 'gamma':
					$this->imagickGamma($file, intval($elems[5]));
					break;

				case '@sepia':
					$this->imagickSepia($file, floatval($elems[5]));
					break;
					
				case '@corners':
					$this->imagickRoundCorners($file, intval($elems[5]));
					break;

				case '@polaroid':
					$this->imagickPolaroid($file, floatval($elems[5]));
					break;
			}

			// effect
			switch($elems[6]) {
				case 'crop':
					$this->imagickCrop($file, $elems[7]);
					break;
			}
		}
		else {
			$this->logger->error(__METHOD__ . ' > Not expected amount of parameters', array($elems));
		}

		GeneralUtility::fixPermissions($file);
	}


	private function imagickGamma($file, $value) {
	
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->gammaImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}		
	}

/* // unused
	private function imagickBlur($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->blurImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}		
	}
*/

	private function imagickSharpen($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);

			$arr = GeneralUtility::trimExplode('x', $value);
			$radius = $arr[0];
			$sigma = $arr[1];
		
			$newIm->sharpenImage($radius, $sigma);

			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickRotate($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->rotateImage(new \ImagickPixel(), $value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

/* // unused
	private function imagickSolarize($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;
		
		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->solarizeImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickSwirl($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->swirlImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickWawe($file, $value1, $value2) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value1, $value2));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->waveImage($value1, $value2);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickCharcoal($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->charcoalImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}
*/

	private function imagickGray($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->setImageType(\Imagick::IMGTYPE_GRAYSCALE);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

/* // unused
	private function imagickEdge($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->edgeImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickEmboss($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->embossImage(0);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickFlip($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;
		
		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->flipImage();
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickFlop($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->flopImage();
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickColors($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->quantizeImage($value, $newIm->getImageColorspace(), 0, false, false);
				// Only save one pixel of each color
			$newIm->uniqueImageColors();
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickShear($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->shearImage($newIm->getImageBackgroundColor(), $value, $value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickInvert($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->negateImage(0);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}
*/

	private function imagickNormalize($file) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);
		
			$newIm->normalizeImage();
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickContrast($file, $value = 1) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);

			$newIm->contrastImage($value);
		
			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickSepia($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);

			$newIm->sepiaToneImage($value); // >= Imagick 2.0.0

			$newIm->writeImage($file);
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}
	
	private function imagickRoundCorners($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->setBackgroundColor(new \ImagickPixel('transparent'));
			$newIm->readImage($file);

			$newIm->roundCorners($value, $value);
			
			//if ($this->ImageSupportsTransparency($file)) {				
				$newIm->writeImage($file);
			/*} else {
				$fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$one = 1;
				$strNewFileName = str_replace($fileExt, 'png', $file, $one);

				if ($this->debug) $this->logger->debug(__METHOD__  . ' File: ', array('fileExt' => $fileExt, 'strNewFileName' => $strNewFileName));
				
				$color = new \ImagickPixel('black');
				$alpha = 0.0; // Fully transparent
				$fuzz = 0.5 * $this->quantumRange;

				if (version_compare(\Imagick::IMAGICK_EXTVER, '3.3.0', '>=')) {
					$newIm->transparentPaintImage($color, $alpha, $fuzz, false);
				} else {
					$newIm->paintTransparentImage($color, $alpha, $fuzz);
				}

				$newIm->setImageFormat('png');
				
				$newIm->writeImage($strNewFileName);
			}*/
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickPolaroid($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->setBackgroundColor(new \ImagickPixel('transparent'));
			$newIm->readImage($file);

			// polaroidImage() changes image geometry so we have to resize images after aplying the effect
			$geo = $newIm->getImageGeometry();			
			$newIm->polaroidImage(new \ImagickDraw(), $value); // IM >= 6.3.2			
			$newIm->resizeImage($geo['width'], $geo['height'], $this->gfxConf['windowing_filter'], 1);

			//if ($this->ImageSupportsTransparency($file)) {				
				$newIm->writeImage($file);
			/*} else {
				$fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$one = 1;
				$strNewFileName = str_replace($fileExt, 'png', $file, $one);

				if ($this->debug) $this->logger->debug('Polaroid params: ', array('ext' => $fileExt, 'new_name' => $strNewFileName));
				$color = new \ImagickPixel('black');
				$alpha = 0.0; // Fully transparent
				$fuzz = 0.5 * $this->quantumRange;

				if (version_compare($this->imagick_version, '3.3.0', '>=')) {
					$newIm->transparentPaintImage($color, $alpha, $fuzz, false);
				} else {
					$newIm->paintTransparentImage($color, $alpha, $fuzz);
				}
				$newIm->setImageFormat('png');
				
				$newIm->writeImage($strNewFileName);
			}*/			
			$newIm->destroy();
			
			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	private function imagickCrop($file, $value) {

		if ($this->debug) $this->logger->debug(__METHOD__  . ' OK', array($file, $value));

		if ($this->NO_IMAGICK) return false;

		try {
			$newIm = new \Imagick();
			$newIm->readImage($file);

			$strVal = str_replace('!', '', $value);
			$arr = GeneralUtility::trimExplode('+', $strVal);
			$dims = $arr[0];
			$x = $arr[1];
			$y = $arr[2];
			$arr = GeneralUtility::trimExplode('x', $dims);
			$w = $arr[0];
			$h = $arr[1];

			$newIm->cropImage($w, $h, $x, $y);

			$newIm->writeImage($file);
			$newIm->destroy();

			return TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return FALSE;
		}
	}

	/**
     * Returns an array with detailed image info.
     *
     * @param 	string	$imagefile File path
	 * @return	array	Image information
     */
	public function imagickGetDetailedImageInfo($imagefile) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($imagefile));

		if ($this->NO_IMAGICK) return [];

		if (!GeneralUtility::isAbsPath($imagefile)) {
			$file = GeneralUtility::getFileAbsFileName($imagefile, FALSE);
		} else {
			$file = $imagefile;
		}

		try {
			$im = new \Imagick();
			$im->readImage($file);
			$identify = $im->identifyImage();

			$res = array(
				'Basic image properties' => array(
					'Image dimensions: ' => $identify['geometry']['width'] . 'x' . $identify['geometry']['height'],
					'Image format: ' => $identify['format'],
					'Image type: ' => $identify['type'],
					'Colorspace: ' => $identify['colorSpace'],
					'Units: ' => $identify['units'],
					'Compression: ' => $identify['compression']
				)
			);
			if (!empty($identify['resolution']['x'])) {			
				$res['Basic image properties'] = array_merge($res['Basic image properties'], 
					array(
						'Resolution: ' => $identify['resolution']['x'] . 'x' . $identify['resolution']['y'] . ' dpi'
					)
				);
			}

			$res['All image properties'] = array();
			foreach ( $im->getImageProperties() as $k => $v ) {
				$res['All image properties'] = array_merge($res['All image properties'], array($k => $v));
			}

			$res['All image profiles'] = array();
			foreach ( $im->getImageProfiles() as $k => $v ) {
				$res['All image profiles'] = array_merge($res['All image profiles'], array(
					$k => '(size: ' . GeneralUtility::formatSize(strlen( $v ), ' | KB| MB| GB') . ')'
				));
			}

			$im->destroy();
			
			return $res;				
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
			return [];
		}
	}


    /**
     * Creates proportional thumbnails
     * 
     * @param \Imagick $imObj
     * @param integer $w - image width
     * @param integer $h - image height
     */
	private function imagickThumbProportional(&$imObj, $w, $h) {
	
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;

		// Resizes to whichever is larger, width or height
		if ($imObj->getImageHeight() <= $imObj->getImageWidth()) {
			// Resize image using the lanczos resampling algorithm based on width
			$imObj->resizeImage($w, 0, \Imagick::FILTER_LANCZOS, 1);
		} else {
			// Resize image using the lanczos resampling algorithm based on height
			$imObj->resizeImage(0, $h, \Imagick::FILTER_LANCZOS, 1);
		}
	}
	

    /**
     * Creates cropped thumbnails
     * 
     * @param \Imagick $imObj
     * @param integer $w - image width
     * @param integer $h - image height
     */
	private function imagickThumbCropped(&$imObj, $w, $h) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;
		
		$imObj->cropThumbnailImage($w, $h);
	}

	
    /**
     * Creates sampled thumbnails
     * 
     * @param \Imagick $imObj
     * @param integer $w - image width
     * @param integer $h - image height
     */
	private function imagickThumbSampled(&$imObj, $w, $h) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK');

		if ($this->NO_IMAGICK) return;
		
		$imObj->sampleImage($w, $h);
	}	

	
	public function imagickThumbnailImage($fileIn, $fileOut, $w, $h) {

		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($fileIn, $fileOut, $w, $h));

		if ($this->NO_IMAGICK) return false;

		$bRes = FALSE;
		$imgDPI = intval($this->gfxConf['imagesDPI']);
		
		try {
			$newIm = new \Imagick($fileIn);
			if ($imgDPI > 0)
				$newIm->setImageResolution($imgDPI, $imgDPI);

			if ($this->gfxConf['im_useStripProfileByDefault']) {
				$newIm->stripImage();
			}
			
			switch($this->gfxConf['thumbnailingMethod']) {
				case 'CROPPED':
					$this->imagickThumbCropped($newIm, $w, $h);
					break;
					
				case 'SAMPLED':
					$this->imagickThumbSampled($newIm, $w, $h);
					break;
					
				default:
					$this->imagickThumbProportional($newIm, $w, $h);
					break;							
			}
	
			$this->imagickOptimizeObject($newIm);
	
			$newIm->writeImage($fileOut);			
			$newIm->destroy();

			$bRes = TRUE;
		}
		catch(\ImagickException $e) {
			
			$sMsg = __METHOD__ . ' >> ' . $e->getMessage();
			if ($this->debug) {
				$this->logger->error($sMsg);
			} else {
				GeneralUtility::sysLog($sMsg, $this->extKey, GeneralUtility::SYSLOG_SEVERITY_WARNING);
			}
		}

		return $bRes;
	}

	/*
	/**
	 *  @brief Checks if image format supports transparency
	 *  
	 *  @param [in] $strFile File path
	 *  @return TRUE if supports, othervise FALSE
	 * /
	private function ImageSupportsTransparency($strFile) {
		
		if ($this->debug) $this->logger->debug(__METHOD__ . ' OK', array($strFile));
		
		$bRes = FALSE;
		$fileExt = strtolower(pathinfo($strFile, PATHINFO_EXTENSION));
		if (in_array($fileExt, $this->transparentFormats)) {
			$bRes = TRUE;
		}
		if ($this->debug) $this->logger->debug(__METHOD__  . ' Transparency: ' . ($bRes ? 'TRUE' : 'FALSE'));

		return $bRes;
	}
	*/
}
