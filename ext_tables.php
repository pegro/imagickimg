<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE === 'BE') {

	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][10] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.10', 31);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][11] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.11', 32);
	$GLOBALS['TCA']['tt_content']['columns']['image_effects']['config']['items'][12] = array('LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang.xlf:image_effects.I.12', 33);
	
	// unserializing the extension configuration
	$_EXTCONF = unserialize($_EXTCONF);
/*
	if ($_EXTCONF['showTestModule']) {
		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
			'ImagickImgTeam.' . $_EXTKEY,
			'system',
			'imagickTest',
			'top',
			array(
				'ImagickInfo' => 'index',
				'ImagickTests' => 'index,read,write,convert,scale,combine',
			),
			array(
				'access' => 'user,group',
				'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Backend/Icons/ModuleIcon.png',
				'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf',
			)
		);
	}
*/
}
