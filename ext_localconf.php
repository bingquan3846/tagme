<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'TYPO3.' . $_EXTKEY,
	'Tagme',
	array(
		'Tag' => 'list,update,delete,show,create',
		
	),
	// non-cacheable actions
	array(
		'Tag' => 'list,create, update, delete',
		
	)
);

?>