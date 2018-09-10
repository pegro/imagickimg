<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE === 'BE') {

	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.10', 31);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.11', 32);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.12', 33);

    // Hooks
    // Show warning in About module if PHP extension Imagick is not loaded
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages'][$_EXTKEY] = \ImagickImgTeam\Imagickimg\WarningMessagePostProcessor::class;
    // Show warning in Reports module if PHP extension Imagick is not loaded
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers'][$_EXTKEY][] = \ImagickImgTeam\Imagickimg\RequirementsCheckUtility::class;
}
