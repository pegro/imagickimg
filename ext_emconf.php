<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "imagickimg".
 *
 * Auto generated 19-03-2016 10:01
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Image Processing via Imagick',
	'description' => 'Resize FE and BE images with Imagick PHP extension. Use all image effects available in standard CE elements like Image or Text with image. Useful on servers where exec() function is disabled.',
	'category' => 'misc',
	'version' => '0.4.0',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearcacheonload' => 0,
	'author' => 'Radu Dumbraveanu, Dmitri Paramonov, Tomasz Krawczyk',
	'author_email' => 'vundicind@gmail.com, dimirlan@mail.ru, tomasz@typo3.pl',
	'author_company' => 'ImagickImgTeam',
	'constraints' =>  array(
		'depends' => array(
			'typo3' => '6.2.0-7.6.99',
			'php' => '5.3.7-5.5.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
/*
	'autoload' => array(
		'psr-4' => array(
			'ImagickImgTeam\\Imagickimg\\' => 'Classes/'
		),
		'classmap' => array(
			'Classes/Xclass',
		),
	),
*/
