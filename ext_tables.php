<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied!!!');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModule('user', 'txlfeditorM1', '', t3lib_extMgm::extPath('lfeditor') . 'mod1/');
}

?>