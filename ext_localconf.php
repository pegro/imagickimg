<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// Disable image processing before check if PHP extension Imagick is loaded.
$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = 0;
$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 0;

// Hooks
// Show warning in About module if PHP extension Imagick is not loaded
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = \ImagickImgTeam\Imagickimg\WarningMessagePostProcessor::class;
// Show warning in Reports module if PHP extension Imagick is not loaded
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$_EXTKEY][] = \ImagickImgTeam\Imagickimg\RequirementsCheckUtility::class; 

if (extension_loaded('imagick')) {
	
	// Xclass
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Imaging\\GraphicalFunctions'] = array(
		'className' => 'ImagickImgTeam\\Imagickimg\\Xclass\\GraphicalFunctions'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Imaging\\GifBuilder'] = array(
		'className' => 'ImagickImgTeam\\Imagickimg\\Xclass\\GifBuilder'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\OnlineMedia\\Processing\\PreviewProcessing'] = array(
		'className' => 'ImagickImgTeam\\Imagickimg\\Xclass\\PreviewProcessing'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\Processing\\LocalPreviewHelper'] = array(
		'className' => 'ImagickImgTeam\\Imagickimg\\Xclass\\LocalPreviewHelper'
	);

	// Imagick loaded, so turn on image processing
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path_lzw'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'] = ''; // Not necesary while using Imagick
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress'] = 0; // Don't use TYPO3 work around. Imagick will compress the images.

	// unserializing the extension configuration
	$_EXTCONF = unserialize($_EXTCONF);

	switch($_EXTCONF['windowingFilter']) {
	  case 'POINT':
		$wF = \Imagick::FILTER_POINT;
		break;
	  case 'BOX':
		$wF = \Imagick::FILTER_BOX;
		break;    
	  case 'TRIANGLE':
		$wF = \Imagick::FILTER_TRIANGLE;
		break;
	  case 'HERMITE':
		$wF = \Imagick::FILTER_HERMITE;
		break;
	  case 'HANNING':
		$wF = \Imagick::FILTER_HANNING;
		break;
	  case 'HAMMING':
		$wF = \Imagick::FILTER_HAMMING;
		break;
	  case 'BLACKMAN':
		$wF = \Imagick::FILTER_BLACKMAN;
		break;
	  case 'GAUSSIAN':
		$wF = \Imagick::FILTER_GAUSSIAN;
		break;
	  case 'QUADRATIC':
		$wF = \Imagick::FILTER_QUADRIC;
		break;
	  case 'CUBIC':
		$wF = \Imagick::FILTER_CUBIC;
		break;
	  case 'CATROM':
		$wF = \Imagick::FILTER_CATROM;
		break;
	  case 'MITCHELL':
		$wF = \Imagick::FILTER_MITCHELL;
		break;
	  case 'LANCZOS':
		$wF = \Imagick::FILTER_LANCZOS;
		break;
	  case 'BESSEL':
		$wF = \Imagick::FILTER_BESSEL;
		break;
	  case 'SINC':
		$wF = \Imagick::FILTER_SINC;
		break;
	  default:
		$wF = \Imagick::FILTER_CATROM;
	}

	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick'] = 1;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['windowing_filter'] = $wF;
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagesDPI'] = $_EXTCONF['imagesDPI'];
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnailingMethod'] = $_EXTCONF['thumbnailingMethod'];
	$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick_debug'] = $_EXTCONF['debug'];

	if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagick_debug']) {

		$GLOBALS['TYPO3_CONF_VARS']['LOG']['ImagickImgTeam']['Imagickimg']['writerConfiguration'] = array(
			// configuration for WARNING severity, including all
			// levels with higher severity (ERROR, CRITICAL, EMERGENCY)
			\TYPO3\CMS\Core\Log\LogLevel::DEBUG => array(
				// add a FileWriter
				'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
					// configuration for the writer
					'logFile' => 'typo3temp/var/logs/imagickimg.log'
				)
			)
		);
	}
}
