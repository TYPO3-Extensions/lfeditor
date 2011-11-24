<?php

########################################################################
# Extension Manager/Repository config file for ext "lfeditor".
#
# Auto generated 24-11-2011 16:43
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Language File Editor',
	'description' => 'This module serves several functions for editing of language files.
					  Translators, extension authors and simple users are supported with
					  special functions for each of them.
					  Following functions are implemented in this module:
					  * Formats: PHP, XML and XLF
					  * enhanced insert types (textarea, enhanced textarea, wysiwig)
					  * conversion of formats into the other supported oned
					  * splitting and merging of language files
					  * workspaces (local (only for backend modules) and global)
					  * simple editing of constants and languages
					  * flexible search and view of constants and values
					  * meta information handling
					  * backups, recovering and diff view',
	'category' => 'module',
	'shy' => 0,
	'version' => '2.8.0',
	'doNotLoadInFE' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Stefan Galinski',
	'author_email' => 'Stefan.Galinski@gmail.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-5.3.99',
			'typo3' => '4.3.0-4.6.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:47:{s:21:"ext_conf_template.txt";s:4:"253d";s:12:"ext_icon.gif";s:4:"9905";s:14:"ext_tables.php";s:4:"f217";s:14:"doc/manual.sxw";s:4:"ce20";s:26:"mod1/class.LFException.php";s:4:"3acf";s:20:"mod1/class.sgLib.php";s:4:"718e";s:36:"mod1/class.tx_lfeditor_mod1_file.php";s:4:"3764";s:43:"mod1/class.tx_lfeditor_mod1_file_backup.php";s:4:"50a7";s:41:"mod1/class.tx_lfeditor_mod1_file_base.php";s:4:"ef0f";s:44:"mod1/class.tx_lfeditor_mod1_file_basePHP.php";s:4:"0e4a";s:44:"mod1/class.tx_lfeditor_mod1_file_baseXLF.php";s:4:"d989";s:44:"mod1/class.tx_lfeditor_mod1_file_baseXML.php";s:4:"ed24";s:40:"mod1/class.tx_lfeditor_mod1_file_xll.php";s:4:"1efd";s:43:"mod1/class.tx_lfeditor_mod1_file_xllPHP.php";s:4:"0a40";s:43:"mod1/class.tx_lfeditor_mod1_file_xllXML.php";s:4:"626c";s:41:"mod1/class.tx_lfeditor_mod1_functions.php";s:4:"ed85";s:40:"mod1/class.tx_lfeditor_mod1_template.php";s:4:"a8f0";s:23:"mod1/class.typo3Lib.php";s:4:"1b0c";s:13:"mod1/conf.php";s:4:"a5b8";s:21:"mod1/da.locallang.xml";s:4:"e04f";s:25:"mod1/da.locallang_mod.xml";s:4:"5d3b";s:21:"mod1/de.locallang.xml";s:4:"8181";s:24:"mod1/de.locallang_fe.xlf";s:4:"146e";s:25:"mod1/de.locallang_mod.xml";s:4:"5d55";s:21:"mod1/fi.locallang.xml";s:4:"644f";s:25:"mod1/fi.locallang_mod.xml";s:4:"3557";s:14:"mod1/index.php";s:4:"e2f3";s:18:"mod1/locallang.xml";s:4:"49ae";s:22:"mod1/locallang_mod.xml";s:4:"d06c";s:19:"mod1/moduleicon.png";s:4:"f2a8";s:22:"mod1/textareaResize.js";s:4:"3ad8";s:21:"mod1/tinyMCEConfig.js";s:4:"ca0a";s:25:"mod1/tx_lfeditor_mod1.css";s:4:"d0a5";s:24:"mod1/tx_lfeditor_mod1.js";s:4:"1305";s:19:"res/images/diff.gif";s:4:"3ba9";s:22:"res/images/garbage.gif";s:4:"5d02";s:19:"res/images/join.gif";s:4:"86ea";s:25:"res/images/joinBottom.gif";s:4:"3822";s:19:"res/images/line.gif";s:4:"d3d7";s:19:"res/images/mail.gif";s:4:"aa1c";s:22:"res/images/recover.gif";s:4:"ee1a";s:24:"res/images/treeMinus.gif";s:4:"dd7a";s:30:"res/images/treeMinusBottom.gif";s:4:"a1b6";s:23:"res/images/treePlus.gif";s:4:"86da";s:29:"res/images/treePlusBottom.gif";s:4:"6ac4";s:19:"res/zip/zip.lib.php";s:4:"9a42";s:24:"templates/main_mod1.html";s:4:"4dac";}',
	'suggests' => array(
	),
);

?>