<?php

class PreviewProcessing extends TYPO3\CMS\Core\Resource\OnlineMedia\Processing\PreviewProcessing {

	/**
	 * TODO convert to imagick
	 *
	 * @param string $originalFileName
	 * @param string $temporaryFileName
	 * @param array $configuration
	 */
	protected function resizeImage($originalFileName, $temporaryFileName, $configuration)
	{
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