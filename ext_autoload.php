<?php
// This file will be ignored by TYPO3 7.x

$arr = array();
if (version_compare(TYPO3_version, '6.0', '>=')) {

	if (extension_loaded('imagick')) {
		$extensionClassesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('imagickimg') . 'Classes/';

		$arr = array(
			'GraphicalFunctions' => $extensionClassesPath . 'Xclass/GraphicalFunctions.php',
			'GifBuilder' => $extensionClassesPath . 'Xclass/GifBuilder.php',
			'ThumbnailView' => $extensionClassesPath . 'Xclass/ThumbnailView.php',
			'LocalPreviewHelper' => $extensionClassesPath . 'Xclass/LocalPreviewHelper.php',
			'ContentObjectRenderer' => $extensionClassesPath . 'Xclass/ContentObjectRenderer.php',
			'ElementInformationController' => $extensionClassesPath . 'Xclass/ElementInformationController.php'
		);
	}
}

return $arr;
