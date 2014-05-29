<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) Stefan Galinski (stefan.galinski@gmail.com)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
$beUser = $GLOBALS['BE_USER'];
$beUser->modAccess($MCONF, 1);

/** @var \TYPO3\CMS\Lang\LanguageService $lang */
$lang = $GLOBALS['LANG'];

if (is_file(t3lib_extMgm::extPath('lfeditor') . 'mod1/locallang.xlf')) {
	$lang->includeLLFile('EXT:lfeditor/mod1/locallang.xlf');
} elseif (is_file(t3lib_extMgm::extPath('lfeditor') . 'mod1/locallang.xml')) {
	$lang->includeLLFile('EXT:lfeditor/mod1/locallang.xml');
} else {
	$lang->includeLLFile('EXT:lfeditor/mod1/locallang.php');
}

// still needed for TYPO3 4.5
$lfeditorPath = t3lib_extMgm::extPath('lfeditor');
require_once($lfeditorPath . 'mod1/class.typo3Lib.php');
require_once($lfeditorPath . 'mod1/class.sgLib.php');
require_once($lfeditorPath . 'mod1/class.LFException.php');

/**
 * Module 'LFEditor' for the 'lfeditor' extension
 */
class tx_lfeditor_module1 extends t3lib_SCbase {

	#######################################
	############## variables ##############
	#######################################

	/**
	 * @var array page access
	 * @see main()
	 */
	public $pageinfo;

	/**
	 * @var array extension configuration
	 * @see prepareConfig()
	 */
	private $extConfig;

	/**
	 * @var tx_lfeditor_mod1_file_basePHP
	 */
	private $fileObj;

	/**
	 * @var tx_lfeditor_mod1_file_basePHP
	 */
	private $convObj;

	/**
	 * @var tx_lfeditor_mod1_file_backup
	 */
	private $backupObj;

	#######################################
	############ main functions ###########
	#######################################

	/**
	 * Constructor
	 */
	public function __construct() {
		// prepare configuration
		$this->prepareConfig();

		// set error wrap
		$errorWrap = '<p class="tx-lfeditor-error">|</p>';
		$noticeWrap = '<p class="tx-lfeditor-notice">|</p>';
		LFException::setWrap($errorWrap, $noticeWrap);

		parent::init();
	}

	/**
	 * Main function of the module. Writes the content to $this->content
	 *
	 * @throws LFException raised if access denied
	 * @return void
	 */
	public function main() {
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('lfeditor') . 'templates/main_mod1.html');
		$this->doc->docType = 'xhtml_trans';
		$this->doc->form = '<form action="" method="post" name="mainForm">';

		// must be set exactly here or the insert mode needs an additional page refresh
		$functionMenu = $this->getFuncMenu('function');
		$this->menuInsertMode();
		$insertModeMenu = $this->getFuncMenu('insertMode');

		// include tinymce or normal textarea (with resize bar) script
		$modPath = t3lib_extMgm::extRelPath('lfeditor');
		if ($this->MOD_SETTINGS['insertMode'] === 'tinyMCE') {
			require(t3lib_extMgm::extPath('tinymce') . 'class.tinymce.php');
			$tinyMCE = new tinyMCE();
			$tinyMCE->loadConfiguration($this->extConfig['pathTinyMCEConfig']);
			$this->doc->JScode = $tinyMCE->getJS();
		} else {
			$this->doc->getPageRenderer()->addJsFile($modPath . 'mod1/textareaResize.js');
		}

		$this->doc->JScode .= '
			<script type="text/javascript">
				var script_ended = 0;
				function jumpToUrl(URL) {
					document.location = URL;
				}
				var treeHide = ' . intval($this->extConfig['treeHide']) . ';
			</script>';

		$this->doc->postCode = '
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.theMenu) top.theMenu.recentuid = ' . intval($this->id) . ';
			</script>';

		$this->doc->getPageRenderer()->addJsFile($modPath . 'mod1/tx_lfeditor_mod1.js');
		$this->doc->getPageRenderer()->addCssFile($modPath . 'mod1/' . $this->extConfig['pathCSS']);

		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		if ((!$this->id || !$access) && (!$GLOBALS['BE_USER']->user['uid'] || $this->id)) {
			throw new LFException('failure.access.denied');
		}

		try {
			$moduleContent = $this->moduleContent();
		} catch (LFException $e) {
			$moduleContent = $e->getGeneratedContent() . $e->getMessage();
		}

		// setting up the buttons and markers
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'FUNC_MENU' => $functionMenu,
			'INSERT_MODE' => $insertModeMenu,
			'CONTENT' => $moduleContent,
		);

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// build the <body> for the module
		$this->content = $this->doc->startPage($lang->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
	}

	/**
	 * Prints the final content...
	 *
	 * @param string $extraContent extra content (appended at the string)
	 * @return void
	 */
	public function printContent($extraContent = '') {
		$this->content = $this->doc->insertStylesAndJS($this->content);
		echo $this->content . $extraContent . $this->doc->endPage();
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array all available buttons as an associative array
	 */
	protected function getButtons() {
		$buttons = array(
			'shortcut' => ''
		);

		/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
		$beUser = $GLOBALS['BE_USER'];

		if ($beUser->mayMakeShortcut()) {
			$selKeys = implode(',', array_keys($this->MOD_MENU));
			$icon = $this->doc->makeShortcutIcon('id', $selKeys, $this->MCONF['name']);
			$buttons['shortcut'] = $icon;
		}

		return $buttons;
	}

	#######################################
	########## config functions ###########
	#######################################

	/**
	 * preparation and check of the configuration
	 *
	 * Note that the default value will be set, if a option check fails.
	 *
	 * @return void
	 */
	private function prepareConfig() {
		// unserialize the configuration
		$this->extConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['lfeditor']);

		// regular expressions
		$this->extConfig['searchRegex'] = '/^[a-z0-9_]*locallang[a-z0-9_-]*\.(php|xml|xlf)$/i';
		if (!preg_match('/^\/.*\/.*$/', $this->extConfig['extIgnore'])) {
			$this->extConfig['extIgnore'] = '/^csh_.*$/';
		}

		// some integer values
		$this->extConfig['viewStateExt'] = intval($this->extConfig['viewStateExt']);
		$this->extConfig['numTextAreaRows'] = intval($this->extConfig['numTextAreaRows']);
		$this->extConfig['numSiteConsts'] = intval($this->extConfig['numSiteConsts']);
		$this->extConfig['anzBackup'] = intval($this->extConfig['anzBackup']);
		$this->extConfig['viewStateExt'] = intval($this->extConfig['viewStateExt']);

		// paths and files (dont need to exist)
		$this->extConfig['pathBackup'] = typo3Lib::fixFilePath(
				PATH_site . '/' .
				$this->extConfig['pathBackup']
			) . '/';
		$this->extConfig['metaFile'] = typo3Lib::fixFilePath(
			PATH_site . '/' .
			$this->extConfig['metaFile']
		);
		$this->extConfig['pathXLLFiles'] = typo3Lib::fixFilePath(
				PATH_site . '/' .
				$this->extConfig['pathXLLFiles']
			) . '/';

		// files
		$this->extConfig['pathCSS'] = 'tx_lfeditor_mod1.css';
		$this->extConfig['pathTinyMCEConfig'] = PATH_site .
			t3lib_extMgm::siteRelPath('lfeditor') . 'mod1/tinyMCEConfig.js';

		// languages (default is forbidden)
		if (!empty($this->extConfig['viewLanguages'])) {
			$langs = explode(',', $this->extConfig['viewLanguages']);
			unset($this->extConfig['viewLanguages']);
			foreach ($langs as $lang) {
				if ($lang != 'default') {
					$this->extConfig['viewLanguages'][] = $lang;
				}
			}
		}
	}

	#######################################
	####### object initializations ########
	#######################################

	/**
	 * creates and instantiates a file object
	 *
	 * Naming Convention:
	 * tx_lfeditor_mod1_file_<workspace><filetype>
	 *
	 * @throws LFException raised if the the object cant be generated or language file not read
	 * @throws Exception|LFException
	 * @param string $langFile
	 * @param string $extPath
	 * @param string $mode
	 * @param bool $flagReadFile
	 * @return void
	 */
	private function initFileObject($langFile, $extPath, $mode, $flagReadFile = TRUE) {
		$mode = ($mode ? : 'base');

		// xll specific
		try {
			$typo3RelFile = '';
			if ($mode == 'xll') {
				try {
					$typo3RelFile = typo3Lib::transTypo3File($extPath . '/' . $langFile, FALSE);
				} catch (Exception $e) {
					$typo3RelFile = '';
				}
				$xllFile = typo3Lib::fixFilePath(
					PATH_site . '/' .
					$GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$typo3RelFile]
				);
				if (is_file($xllFile)) {
					$langFile = basename($xllFile);
					$extPath = dirname($xllFile);
				} else {
					$langFile = t3lib_div::shortMD5(md5(microtime())) . '.' .
						sgLib::getFileExtension($langFile);
					$extPath = $this->extConfig['pathXLLFiles'];
					$flagReadFile = FALSE;
				}
			}
			$fileType = sgLib::getFileExtension($langFile);
		} catch (Exception $e) {
			throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
		}

		// create file object
		$className = 'tx_lfeditor_mod1_file_' . $mode . strtoupper($fileType);
		if (!class_exists($className)) {
			throw new LFException('failure.langfile.unknownType');
		}
		$this->fileObj = t3lib_div::makeInstance($className);

		try {
			if ($mode == 'xll') {
				$this->fileObj->init($langFile, $extPath, $typo3RelFile);
			} else {
				$this->fileObj->init($langFile, $extPath);
			}

			if ($flagReadFile) {
				$this->fileObj->readFile();
			}
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * init backup object
	 *
	 * @throws LFException raised if directories cant be created or backup class instantiated
	 * @throws Exception|LFException
	 * @param string $mode workspace
	 * @param boolean|array $infos set to true if you want use information from the file object
	 * @return void
	 */
	private function initBackupObject($mode, $infos = NULL) {
		$mode = ($mode ? : 'base');

		// create backup and meta directory
		$backupPath = $this->extConfig['pathBackup'];
		$metaFile = $this->extConfig['metaFile'];
		try {
			sgLib::createDir($backupPath, PATH_site);
			sgLib::createDir(dirname($metaFile), PATH_site);
		} catch (Exception $e) {
			throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
		}

		// get information
		$extPath = '';
		$langFile = '';
		if (!is_array($infos)) {
			// build language file and extension path
			if ($mode == 'xll') {
				try {
					$typo3RelFile = $this->fileObj->getVar('typo3RelFile');
					$typo3AbsFile = typo3Lib::transTypo3File($typo3RelFile, TRUE);
				} catch (Exception $e) {
					throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
				}

				$langFile = sgLib::trimPath('EXT:', $typo3RelFile);
				$langFile = substr($langFile, strpos($langFile, '/') + 1);

				$extPath = sgLib::trimPath(
					$langFile, sgLib::trimPath(
						PATH_site,
						$typo3AbsFile
					), '/'
				);
			} else {
				$extPath = sgLib::trimPath(PATH_site, $this->fileObj->getVar('absPath'), '/');
				$langFile = $this->fileObj->getVar('relFile');
			}

			// set data information
			$informations['localLang'] = $this->fileObj->getLocalLangData();
			$informations['originLang'] = $this->fileObj->getOriginLangData();
			$informations['meta'] = $this->fileObj->getMetaData();
		}

		// set information
		$informations['workspace'] = $mode;
		$informations['extPath'] = is_array($infos) ? $infos['extPath'] : $extPath;
		$informations['langFile'] = is_array($infos) ? $infos['langFile'] : $langFile;

		// create and initialize the backup object
		try {
			$this->backupObj = t3lib_div::makeInstance('tx_lfeditor_mod1_file_backup');
			$this->backupObj->init('', $backupPath, $metaFile);
			$this->backupObj->setVar($informations);
		} catch (LFException $e) {
			throw $e;
		}
	}

	########################################
	######## menu generation methods #######
	########################################

	/**
	 * returns a generated Menu
	 *
	 * @param string $key contains the array key of the menu
	 * @return string generated Menu (HTML-Code)
	 */
	private function getFuncMenu($key) {
		$retVal = t3lib_BEfunc::getFuncMenu(
			$this->id, 'SET[' . $key . ']',
			$this->MOD_SETTINGS[$key], $this->MOD_MENU[$key]
		);

		// problem with # char in uris ... :-(
		$this->MOD_SETTINGS[$key] = str_replace('$*-*$', '#', $this->MOD_SETTINGS[$key]);

		return $retVal;
	}

	/**
	 * adds items to the MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$this->MOD_MENU = array(
			'function' => array(
				'general' => $lang->getLL('function.general.general'),
				'langfile.edit' => $lang->getLL('function.langfile.edit'),
				'const.edit' => $lang->getLL('function.const.edit.edit'),
				'const.add' => $lang->getLL('function.const.add.add'),
				'const.delete' => $lang->getLL('function.const.delete.delete'),
				'const.rename' => $lang->getLL('function.const.rename.rename'),
				'const.search' => $lang->getLL('function.const.search.search'),
				'const.treeview' => $lang->getLL('function.const.treeview.treeview'),
				'backupMgr' => $lang->getLL('function.backupMgr.backupMgr')
			)
		);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the extension menu selector.
	 *
	 * @throws LFException raised if no extensions are found
	 * @return void
	 */
	private function menuExtList() {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// search extensions
		$tmpExtList = array();
		try {
			// local extensions
			if ($this->extConfig['viewLocalExt']) {
				if (count(
					$content = tx_lfeditor_mod1_functions::searchExtensions(
						PATH_site . typo3Lib::pathLocalExt, $this->extConfig['viewStateExt'],
						$this->extConfig['extIgnore']
					)
				)
				) {
					$tmpExtList[$lang->getLL('ext.local')] = $content;
				}
			}

			// global extensions
			if ($this->extConfig['viewGlobalExt']) {
				if (count(
					$content = tx_lfeditor_mod1_functions::searchExtensions(
						PATH_site . typo3Lib::pathGlobalExt, $this->extConfig['viewStateExt'],
						$this->extConfig['extIgnore']
					)
				)
				) {
					$tmpExtList[$lang->getLL('ext.global')] = $content;
				}
			}

			// system extensions
			if ($this->extConfig['viewSysExt']) {
				if (count(
					$content = tx_lfeditor_mod1_functions::searchExtensions(
						PATH_site . typo3Lib::pathSysExt, $this->extConfig['viewStateExt'],
						$this->extConfig['extIgnore']
					)
				)
				) {
					$tmpExtList[$lang->getLL('ext.system')] = $content;
				}
			}
		} catch (Exception $e) {
			throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
		}

		// check extension array
		if (!count($tmpExtList)) {
			throw new LFException('failure.search.noExtension');
		}

		// create list
		$extList = tx_lfeditor_mod1_functions::prepareExtList($tmpExtList);
		$extList = array_merge(array(PATH_site . 'fileadmin' => 'fileadmin/', ''), $extList);
		$this->MOD_MENU = array(
			'extList' => $extList
		);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the language file menu selector.
	 *
	 * @throws LFException raised if no language files are found
	 * @return void
	 */
	private function menuLangFileList() {
		// check
		if (empty($this->MOD_SETTINGS['extList'])) {
			throw new LFException('failure.search.noLangFile');
		}

		// search and prepare files
		try {
			$files = sgLib::searchFiles(
				$this->MOD_SETTINGS['extList'],
				$this->extConfig['searchRegex']
			);
		} catch (Exception $e) {
			throw new LFException('failure.search.noLangFile', 0,
				'(' . $e->getMessage() . ')');
		}

		$fileArray = array();
		if (count($files)) {
			foreach ($files as $file) {
				$filename = substr($file, strlen($this->MOD_SETTINGS['extList']) + 1);
				$fileArray[$filename] = $filename;
			}
		} else {
			throw new LFException('failure.search.noLangFile');
		}

		// create list
		$this->MOD_MENU = array('langFileList' => $fileArray);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the workspace selector
	 *
	 * @return void
	 */
	private function menuWorkspaceList() {
		if (t3lib_div::compat_version('6.0')) {
			return;
		}

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$wsList['base'] = $lang->getLL('workspace.base');
		$wsList['xll'] = $lang->getLL('workspace.xll');

		$this->MOD_MENU = array('wsList' => $wsList);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the insert mode selector
	 *
	 * @return void
	 */
	private function menuInsertMode() {
		if (!t3lib_extMgm::isLoaded('tinymce')) {
			return;
		}

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$switch['tinyMCE'] = $lang->getLL('select.insertMode.tinyMCE');
		$switch['normal'] = $lang->getLL('select.insertMode.normal');

		$this->MOD_MENU = array('insertMode' => $switch);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the constant type selector
	 *
	 * @return void
	 */
	private function menuConstantType() {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$constTypeList['all'] = $lang->getLL('const.type.all');
		$constTypeList['translated'] = $lang->getLL('const.type.translated');
		$constTypeList['unknown'] = $lang->getLL('const.type.unknown');
		$constTypeList['untranslated'] = $lang->getLL('const.type.untranslated');

		$this->MOD_MENU = array('constTypeList' => $constTypeList);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the language menu selector
	 *
	 * @param array $langData language data
	 * @param string $funcKey keyword of the menuBox
	 * @param string $default optional default value (if you dont want a default let it empty)
	 * @return void
	 */
	private function menuLangList($langData, $funcKey, $default = '') {
		/** @var \TYPO3\CMS\Lang\LanguageService $langInstance */
		$langInstance = $GLOBALS['LANG'];

		// build languages
		$languages = tx_lfeditor_mod1_functions::buildLangArray($this->extConfig['viewLanguages']);
		$langArray = array_merge(array('default'), $languages);
		foreach ($langArray as $lang) {
			$anzConsts = 0;
			if (is_array($langData[$lang])) {
				$anzConsts = count($langData[$lang]);
			}

			$langList[$lang] = $lang . ' (' . $anzConsts . ' ' .
				$langInstance->getLL('const.consts') . ')';
		}
		asort($langList);

		// add default value
		if (!empty($default)) {
			$langList = array_merge(array('###default###' => $default), $langList);
		}

		$this->MOD_MENU = array($funcKey => $langList);
		parent::menuConfig();
	}

	/**
	 * adds items to the MOD_MENU array. Used for the editConst-List
	 *
	 * @param array $langData language data
	 * @param string $default name of default entry
	 * @return void
	 */
	private function menuConstList($langData, $default) {
		// generate constant list
		$constList = array();
		$languages = tx_lfeditor_mod1_functions::buildLangArray();
		foreach ($languages as $language) {
			if (!is_array($langData[$language]) || !count($langData[$language])) {
				continue;
			}

			$constants = array_keys($langData[$language]);
			foreach ($constants as $constant) {
				$constList[str_replace('#', '$*-*$', $constant)] = $constant;
			}
		}

		// sorting and default entry
		asort($constList);
		$constList = array_merge(array('###default###' => $default), $constList);

		$this->MOD_MENU = array('constList' => $constList);
		parent::menuConfig();
	}

	#######################################
	############ exec functions ###########
	#######################################

	/**
	 * splits (with typo3 V4 l10n support) or merges a language file (inclusive backup)
	 *
	 * @throws LFException raised if file could not be splitted or merged (i.e. empty langModes)
	 * @throws Exception|LFException
	 * @param array language shortcuts and their mode (1 = splitNormal, 2 = splitL10n, 3 = merge)
	 * @return void
	 */
	private function execSplitFile($langModes) {
		// check
		if (!is_array($langModes) || !count($langModes)) {
			throw new LFException('failure.langfile.notSplittedOrMerged');
		}

		// rewrite originLang array
		$delLangFiles = array();
		foreach ($langModes as $langKey => $mode) {
			if ($langKey == 'default') {
				continue;
			}

			// get origin of this language
			$origin = $this->fileObj->getOriginLangData($langKey);

			// split or merge
			if ($mode == 1) {
				// nothing to do if the file is already a normal splitted file
				if (typo3lib::checkFileLocation($origin) != 'l10n') {
					if ($this->fileObj->checkLocalizedFile(basename($origin), $langKey)) {
						continue;
					}
				}

				// delete file if was it a l10n file
				if ($this->fileObj->checkLocalizedFile(basename($origin), $langKey)) {
					$delLangFiles[] = $origin;
				}

				$origin = typo3Lib::fixFilePath(
					dirname($this->fileObj->getVar('absFile')) .
					'/' . $this->fileObj->nameLocalizedFile($langKey)
				);
			} elseif ($mode == 2) {
				// nothing to do if the file is already a l10n file
				if (typo3lib::checkFileLocation($origin) == 'l10n') {
					continue;
				}

				// delete file if was it a normal splitted file
				if ($this->fileObj->checkLocalizedFile(basename($origin), $langKey)) {
					$delLangFiles[] = $origin;
				}

				if (is_dir(PATH_site . typo3lib::pathL10n . $langKey)) {
					// generate middle of the path between extension start and file
					try {
						$midPath = typo3Lib::transTypo3File($origin, FALSE);
						$midPath = substr($midPath, 4);
						$midPath = substr($midPath, 0, strrpos($midPath, '/') + 1);

						$origin = PATH_site . typo3lib::pathL10n . $langKey .
							'/' . $midPath . $this->fileObj->nameLocalizedFile($langKey);
					} catch (Exception $e) {
						throw new LFException('failure.langfile.notSplittedOrMerged', 0,
							'(' . $e->getMessage() . ')');
					}
				}
			} elseif ($mode == 3) {
				if ($this->fileObj->checkLocalizedFile(basename($origin), $langKey)) {
					$delLangFiles[] = $origin;
				}
				$origin = $this->fileObj->getVar('absFile');
			} else {
				continue;
			}
			$this->fileObj->setOriginLangData($origin, $langKey);
		}

		// write new language file
		try {
			$this->execWrite(array());
		} catch (LFException $e) {
			throw $e;
		}

		// delete old localized files, if single mode was selected
		try {
			if (count($delLangFiles)) {
				sgLib::deleteFiles($delLangFiles);
			}
		} catch (Exception $e) {
			throw new LFException('failure.langfile.notDeleted', 0,
				'(' . $e->getMessage() . ')');
		}
	}

	/**
	 * converts language files between different formats
	 *
	 * @throws LFException raised if transforming or deletion of old files failed
	 * @throws Exception|LFException
	 * @param string $type new file format
	 * @param string $newFile new relative file
	 * @return void
	 */
	private function execTransform($type, $newFile) {
		// copy current object to convObj
		$this->convObj = clone $this->fileObj;
		unset($this->fileObj);

		// init new language file object (dont try to read file)
		try {
			$this->initFileObject(
				$newFile, $this->convObj->getVar('absPath'),
				$this->MOD_SETTINGS['wsList'], FALSE
			);
		} catch (LFException $e) {
			throw $e;
		}

		// recreate originLang
		$dirNameOfAbsFile = dirname($this->fileObj->getVar('absFile'));
		$origins = $this->convObj->getOriginLangData();
		foreach ($origins as $langKey => $file) {
			// localized or merged language origin
			$newFile = sgLib::setFileExtension($type, $file);
			if ($this->convObj->getVar('workspace') == 'base') {
				if ($this->convObj->checkLocalizedFile(basename($file), $langKey)) {
					$newFile = $dirNameOfAbsFile . '/' . $this->fileObj->nameLocalizedFile($langKey);
				}
			}
			$this->fileObj->setOriginLangData(typo3Lib::fixFilePath($newFile), $langKey);
		}

		// recreate meta data
		$meta = $this->convObj->getMetaData();
		foreach ($meta as $metaIndex => $metaValue) {
			$this->fileObj->setMetaData($metaIndex, $metaValue);
		}

		// copy typo3RelFile if xll workspace is selected
		if ($this->MOD_SETTINGS['wsList'] == 'xll') {
			$this->fileObj->setVar(array('typo3RelFile' => $this->convObj->getVar('typo3RelFile')));
		}

		// write new language file
		try {
			$this->extConfig['execBackup'] = 0;
			$this->execWrite($this->convObj->getLocalLangData());
		} catch (LFException $e) {
			throw $e;
		}

		// delete all old files
		try {
			$delFiles = $this->convObj->getOriginLangData();
			if (is_array($delFiles) && count($delFiles)) {
				sgLib::deleteFiles($delFiles);
			}
		} catch (Exception $e) {
			throw new LFException('failure.langfile.notDeleted', 0,
				'(' . $e->getMessage() . ')');
		}
	}

	/**
	 * executes the deletion of backup files
	 *
	 * @throws LFException raised if a backup file couldnt be deleted
	 * @param array $delFiles files as key and the language file as value
	 * @return void
	 */
	public function execBackupDelete($delFiles) {
		// delete files
		try {
			foreach ($delFiles as $filename => $langFile) {
				$this->backupObj->deleteSpecFile($filename, '', $langFile);
			}
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * restores a backup file
	 *
	 * @throws LFException raised if some unneeded files couldnt be deleted
	 * @throws Exception|LFException
	 * @return void
	 */
	private function execBackupRestore() {
		// get vars
		$localLang = array();
		$meta = array();
		$origLang = $this->fileObj->getLocalLangData();
		$origMeta = $this->fileObj->getMetaData();
		$backupMeta = $this->backupObj->getMetaData();
		$backupLocalLang = $this->backupObj->getLocalLangData();
		$backupOriginLang = $this->backupObj->getOriginLangData();

		// get differences between original and backup file
		$origDiff = tx_lfeditor_mod1_functions::getBackupDiff(1, $origLang, $backupLocalLang);
		$backupDiff = tx_lfeditor_mod1_functions::getBackupDiff(2, $origLang, $backupLocalLang);

		if (count($origDiff)) {
			foreach ($origDiff as $langKey => $data) {
				foreach ($data as $label => $value) {
					if (isset($backupLocalLang[$langKey][$label])) {
						$localLang[$langKey][$label] = $value;
					} else {
						$localLang[$langKey][$label] = '';
					}
				}
			}
		}

		if (count($backupDiff)) {
			foreach ($backupDiff as $langKey => $data) {
				foreach ($data as $label => $value) {
					$localLang[$langKey][$label] = $value;
				}
			}
		}

		// get differences between original and backup meta
		$origDiff = tx_lfeditor_mod1_functions::getMetaDiff(1, $origMeta, $backupMeta);
		$backupDiff = tx_lfeditor_mod1_functions::getMetaDiff(2, $origMeta, $backupMeta);

		if (count($origDiff)) {
			foreach ($origDiff as $label => $value) {
				if (isset($backupMeta[$label])) {
					$meta[$label] = $value;
				} else {
					$meta[$label] = '';
				}
			}
		}

		if (count($backupDiff)) {
			foreach ($backupDiff as $label => $value) {
				$meta[$label] = $value;
			}
		}

		// restore origins of languages
		$deleteFiles = array();
		foreach ($backupOriginLang as $langKey => $file) {
			$curFile = $this->fileObj->getOriginLangData($langKey);
			if ($curFile != $file && $curFile != $this->fileObj->getVar('absFile')) {
				$deleteFiles[] = $curFile;
			}
			$this->fileObj->setOriginLangData($file, $langKey);
		}

		// write modified language array
		try {
			$this->extConfig['execBackup'] = 0;
			$this->execWrite($localLang, $meta, TRUE);
		} catch (LFException $e) {
			throw $e;
		}

		// delete all old files
		try {
			if (count($deleteFiles)) {
				sgLib::deleteFiles($deleteFiles);
			}
		} catch (Exception $e) {
			throw new LFException('failure.langfile.notDeleted', 0,
				'(' . $e->getMessage() . ')');
		}
	}

	/**
	 * exec the backup of files and deletes automatic old files
	 *
	 * @throws LFException raised if backup file cant written or unneeded files cant deleted
	 * @return boolean
	 */
	private function execBackup() {
		// create backup object
		try {
			$this->initBackupObject($this->MOD_SETTINGS['wsList'], TRUE);
		} catch (LFException $e) {
			throw $e;
		}

		// write backup file
		try {
			$this->backupObj->writeFile();
		} catch (LFException $e) {
			throw $e;
		}

		// exec automatic deletion of backup files, if anzBackup greater zero
		if ($this->extConfig['anzBackup'] <= 0) {
			return TRUE;
		}

		// get difference information
		$metaArray = $this->backupObj->getMetaInfos(3);
		$rows = count($metaArray);
		$dif = $rows - $this->extConfig['anzBackup'];

		if ($dif <= 0) {
			return TRUE;
		}

		// sort metaArray
		foreach ($metaArray as $key => $row) {
			$createdAt[$key] = $row['createdAt'];
		}
		array_multisort($createdAt, SORT_DESC, $metaArray);

		// get filenames
		$files = array_keys($metaArray);
		$numberFiles = count($files);

		// delete files
		try {
			for (; $dif > 0; --$dif, --$numberFiles) {
				$this->backupObj->deleteSpecFile($files[$numberFiles - 1]);
			}
		} catch (LFException $e) {
			try { // delete current written file
				$this->backupObj->deleteFile();
			} catch (LFException $e) {
				throw $e;
			}
			throw $e;
		}

		return FALSE;
	}

	/**
	 * executes writing of language files
	 *
	 * @throws LFException raised if file could not be written or some param criterias are not correct
	 * @throws Exception|LFException
	 * @param array $modArray changes (constants with empty values will be deleted)
	 * @param array $modMetaArray meta changes (indexes with empty values will be deleted)
	 * @param boolean $forceDel set to true if you want delete default constants
	 * @return void
	 */
	private function execWrite($modArray, $modMetaArray = array(), $forceDel = FALSE) {
		// checks
		if (!is_array($modArray)) {
			throw new LFException('failure.file.notWritten');
		}

		// execute backup
		try {
			if ($this->extConfig['execBackup']) {
				$this->execBackup();
			}
		} catch (LFException $e) {
			throw $e;
		}

		// set new language data
		foreach ($modArray as $langKey => $data) {
			if (is_array($data)) {
				foreach ($data as $const => $value) {
					$this->fileObj->setLocalLangData($const, $value, $langKey, $forceDel);
				}
			}
		}

		// set changed meta data
		foreach ($modMetaArray as $metaIndex => $metaValue) {
			$this->fileObj->setMetaData($metaIndex, $metaValue);
		}

		// write new language data
		try {
			$this->fileObj->writeFile();
		} catch (LFException $e) {
			throw $e;
		}

		// delete possible language files
		$absFile = $this->fileObj->getVar('absFile');
		$originLang = $this->fileObj->getOriginLangData();
		$emptyFiles = array();
		foreach ($originLang as $lang => $origin) {
			if ($origin == $absFile || !is_file($origin)) {
				continue;
			}

			$langData = $this->fileObj->getLocalLangData($lang);
			if (is_array($langData) && !count($langData)) {
				$emptyFiles[] = $origin;
			}
		}

		// delete all empty language files
		try {
			if (count($emptyFiles)) {
				sgLib::deleteFiles($emptyFiles);
			}
		} catch (Exception $e) {
			throw new LFException('failure.langfile.notDeleted', 0, '(' . $e->getMessage() . ')');
		}

		// reinitialize fileobject
		try {
			$this->initFileObject(
				$this->MOD_SETTINGS['langFileList'],
				$this->MOD_SETTINGS['extList'], $this->MOD_SETTINGS['wsList']
			);
		} catch (LFException $e) {
			throw $e;
		}
	}

	#######################################
	####### main content function #########
	#######################################

	/**
	 * code for output generation of function "general"
	 *
	 * @return string generated html content
	 */
	private function outputFuncGeneral() {
		// get vars
		$patternList = $this->MOD_SETTINGS['patternList'];
		$numTextAreaRows = $this->extConfig['numTextAreaRows'];
		$mailIt = t3lib_div::_POST('mailIt');
		$sendMail = t3lib_div::_POST('sendMail');

		// get information array
		$languages = tx_lfeditor_mod1_functions::buildLangArray($this->extConfig['viewLanguages']);
		$languages = array_merge(array('default'), $languages);
		$infoArray = tx_lfeditor_mod1_functions::genGeneralInfoArray(
			$patternList,
			$languages, $this->fileObj
		);

		// get output
		$email = '';
		if (is_array($mailIt) && !$sendMail) {
			// add mailIt pre selection
			foreach ($infoArray as $langKey => $info) {
				$infoArray[$langKey]['email'] = (isset($mailIt[$langKey]) ? TRUE : FALSE);
			}

			$email = tx_lfeditor_mod1_template::outputGeneralEmail($infoArray['default']['meta'], $numTextAreaRows);
		}

		$fileType = $this->fileObj->getVar('fileType');
		$content = $email . tx_lfeditor_mod1_template::outputGeneral(
				$infoArray, $patternList,
				$numTextAreaRows, ($this->fileObj->getVar('workspace') !== 'base' || $fileType === 'xlf' ? FALSE : TRUE)
			);

		return $content;
	}

	/**
	 * code for all actions of function "general"
	 *
	 * @throws LFException raised if something fails
	 * @throws Exception|LFException
	 * @return boolean true or false (only false if some files should be mailed)
	 */
	private function actionFuncGeneral() {
		// get vars
		$splitFile = t3lib_div::_POST('splitFile');
		$transFile = t3lib_div::_POST('transFile');
		$langModes = t3lib_div::_POST('langModes');
		$language = t3lib_div::_POST('language');
		$metaArray = t3lib_div::_POST('meta');
		$mailIt = t3lib_div::_POST('mailIt');
		$sendMail = t3lib_div::_POST('sendMail');
		$emailToAddress = t3lib_div::_POST('mailItEmailToAddress');
		$emailFromAddress = t3lib_div::_POST('mailItEmailFromAddress');
		$emailSubject = t3lib_div::_POST('mailItEmailSubject');
		$emailText = t3lib_div::_POST('mailItEmailText');

		// redirect
		if (!empty($language)) {
			header(
				'Location: ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') .
				'?M=user_txlfeditorM1&SET[langList]=' . $language . '&SET[function]=const.treeview'
			);
		}

		// zip and mail selected languages
		if (is_array($mailIt)) {
			if (!$sendMail) {
				return FALSE;
			}

			$zipFile = new zipfile();
			foreach ($mailIt as $langKey => $in) {
				$origin = $this->fileObj->getOriginLangData($langKey);
				try {
					$saveOrigin = typo3Lib::transTypo3File($origin, FALSE);
					$saveOrigin = str_replace('EXT:', '', $saveOrigin);
				} catch (Exception $e) {
					$saveOrigin = substr($origin, strlen(PATH_site));
				}
				$zipFile->addFile(file_get_contents($origin), $saveOrigin);
			}
			$dumpBuffer = $zipFile->file();

			// try to send mail
			try {
				sgLib::sendMail(
					$emailSubject, $emailText, $emailFromAddress, $emailToAddress,
					$dumpBuffer, 'files.zip'
				);
			} catch (Exception $e) {
				throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
			}
		}

		// write meta information
		try {
			$this->execWrite(array(), $metaArray);
		} catch (LFException $e) {
			throw $e;
		}

		// split or merge
		if (($splitFile == 1 || $splitFile == 2 || $splitFile == 3) || is_array($langModes)) {
			// set vars
			if ($splitFile != 1 && $splitFile != 2 && $splitFile != 3) {
				$splitFile = 0;
			}
			$langKeys = tx_lfeditor_mod1_functions::buildLangArray();

			// generate langModes
			foreach ($langKeys as $langKey) {
				if (!isset($langModes[$langKey])) {
					$langModes[$langKey] = $splitFile;
				}
			}

			// exec split or merge
			try {
				$this->execSplitFile($langModes);
			} catch (LFException $e) {
				throw $e;
			}

			// reinitialize file object
			try {
				$this->initFileObject(
					$this->MOD_SETTINGS['langFileList'],
					$this->MOD_SETTINGS['extList'], $this->MOD_SETTINGS['wsList']
				);
			} catch (LFException $e) {
				throw $e;
			}
		}

		// transform file
		try {
			if (!empty($transFile) && $this->fileObj->getVar('fileType') != $transFile) {
				$newFile = sgLib::setFileExtension($transFile, $this->fileObj->getVar('relFile'));
				$this->execTransform($transFile, $newFile);
				if ($this->MOD_SETTINGS['wsList'] != 'xll') {
					header(
						'Location: ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') .
						'?M=user_txlfeditorM1&SET[langFileList]=' . $newFile
					);
				}
			}
		} catch (LFException $e) {
			throw $e;
		}

		return TRUE;
	}

	/**
	 * code for output generation of function "langfile.edit"
	 *
	 * @throws LFException
	 * @param array language array
	 * @return string generated html content
	 */
	private function outputFuncLangfileEdit($langData) {
		// user selection
		$langList = $this->MOD_SETTINGS['langList'];
		$patternList = $this->MOD_SETTINGS['langfileEditPatternList'];
		$constTypeList = $this->MOD_SETTINGS['constTypeList'];

		// get language data of user selection
		$langEdit = is_array($langData[$langList]) ? $langData[$langList] : array();
		$langPattern = is_array($langData[$patternList]) ? $langData[$patternList] : array();
		$langDefault = is_array($langData['default']) ? $langData['default'] : array();

		// session related stuff
		$session = t3lib_div::_POST('session'); // used for staying at current page after saving
		$numSessionConsts = intval(t3lib_div::_POST('numSessionConsts'));
		$numLastPageConsts = intval(t3lib_div::_POST('numLastPageConsts'));
		$buttonType = intval(t3lib_div::_POST('buttonType'));
		$sessID = $GLOBALS['BE_USER']->user['username']; // typo3 user session id

		// user configuration
		$numTextAreaRows = $this->extConfig['numTextAreaRows'];
		$maxSiteConsts = $this->extConfig['numSiteConsts'];

		// new translation
		if (!$session || $buttonType <= 0) {
			// adjust number of session constants
			if ($constTypeList == 'untranslated' || $constTypeList == 'translated' ||
				$constTypeList == 'unknown' || $buttonType <= 0
			) {
				$numSessionConsts = 0;
			} elseif (!$session) // session written to file
			{
				$numSessionConsts -= $numLastPageConsts;
			}

			// delete old data in session
			unset($_SESSION[$sessID]['langfileEditNewLangData']);
			unset($_SESSION[$sessID]['langfileEditConstantsList']);

			// get language data
			if ($constTypeList == 'untranslated') {
				$myLangData = array_diff_key($langDefault, $langEdit);
			} elseif ($constTypeList == 'unknown') {
				$myLangData = array_diff_key($langEdit, $langDefault);
			} elseif ($constTypeList == 'translated') {
				$myLangData = array_intersect_key($langDefault, $langEdit);
			} else {
				$myLangData = $langDefault;
			}
			$_SESSION[$sessID]['langfileEditConstantsList'] = array_keys($myLangData);
		} elseif ($buttonType == 1) // back button
		{
			$numSessionConsts -= ($maxSiteConsts + $numLastPageConsts);
		}

		// get language constants
		$langData = $_SESSION[$sessID]['langfileEditConstantsList'];
		$numConsts = count($langData);
		if (!count($langData)) {
			throw new LFException('failure.select.emptyLangDataArray', 1);
		}

		// prepare constant list for this page
		$numLastPageConsts = 0;
		$constValues = array();
		do {
			// check number of session constants
			if ($numSessionConsts >= $numConsts) {
				break;
			}
			++$numLastPageConsts;

			// set constant value (maybe already changed in this session)
			$constant = $langData[$numSessionConsts];
			$editLangVal = $langEdit[$constant];
			if (!isset($_SESSION[$sessID]['langfileEditNewLangData'][$langList][$constant])) {
				$_SESSION[$sessID]['langfileEditNewLangData'][$langList][$constant] = $editLangVal;
			} else {
				$editLangVal = $_SESSION[$sessID]['langfileEditNewLangData'][$langList][$constant];
			}

			// set constant value (maybe already changed in this session)
			$editPatternVal = $langPattern[$constant];
			if (!isset($_SESSION[$sessID]['langfileEditNewLangData'][$patternList][$constant])) {
				$_SESSION[$sessID]['langfileEditNewLangData'][$patternList][$constant] = $editLangVal;
			} else {
				$editPatternVal =
					$_SESSION[$sessID]['langfileEditNewLangData'][$patternList][$constant];
			}

			// save information about the constant
			$constValues[$constant]['edit'] = $editLangVal;
			$constValues[$constant]['pattern'] = $editPatternVal;
			$constValues[$constant]['default'] = $langDefault[$constant];
		} while (++$numSessionConsts % $maxSiteConsts);

		// get output
		$content = tx_lfeditor_mod1_template::outputEditLangfile(
			$constValues, $numSessionConsts,
			$numLastPageConsts, $numConsts, $langList, $patternList,
			// parallel edit mode
			(($patternList != '###default###' && $patternList != $langList) ? TRUE : FALSE),
			($numSessionConsts > $maxSiteConsts ? TRUE : FALSE), // display back button?
			($numSessionConsts < $numConsts ? TRUE : FALSE), // display next button?
			$numTextAreaRows
		);

		return $content;
	}

	/**
	 * code for all actions of function "langfile.edit"
	 *
	 * @throws LFException raised if file could not be written
	 * @return void
	 */
	private function actionFuncLangfileEdit() {
		// get session id
		$sessID = $GLOBALS['BE_USER']->user['username'];

		// get vars
		$newLang = t3lib_div::_POST('newLang');
		$session = t3lib_div::_POST('session');
		$langList = $this->MOD_SETTINGS['langList'];
		$patternList = $this->MOD_SETTINGS['langfileEditPatternList'];

		// write new language file or save information into session
		try {
			$_SESSION[$sessID]['langfileEditNewLangData'][$langList] =
				array_merge(
					$_SESSION[$sessID]['langfileEditNewLangData'][$langList],
					$newLang[$langList]
				);

			// parallel edit mode?
			if ($patternList != '###default###' && $patternList != $langList) {
				$_SESSION[$sessID]['langfileEditNewLangData'][$patternList] =
					array_merge(
						$_SESSION[$sessID]['langfileEditNewLangData'][$patternList],
						$newLang[$patternList]
					);
			}

			// write if no session continued
			if (!$session) {
				$this->execWrite($_SESSION[$sessID]['langfileEditNewLangData']);
			}
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * code for output generation of function "const.edit"
	 *
	 * @throws LFException raised if no constant was selected
	 * @param array language array
	 * @return string generated html content
	 */
	private function outputFuncConstEdit($langData) {
		// get vars
		$constant = $this->MOD_SETTINGS['constList'];
		$numTextAreaRows = $this->extConfig['numTextAreaRows'];

		// checks
		if (empty($constant) || $constant == '###default###') {
			throw new LFException('failure.select.noConst', 1);
		}

		// get output
		$languages = tx_lfeditor_mod1_functions::buildLangArray($this->extConfig['viewLanguages']);
		$langArray = array_merge(array('default'), $languages);
		$content = tx_lfeditor_mod1_template::outputEditConst($langArray, $constant, $langData, $numTextAreaRows);

		return $content;
	}

	/**
	 * code for all actions of function "const.edit"
	 *
	 * @throws LFException raised if language file could not be written
	 * @return void
	 */
	private function actionFuncConstEdit() {
		// get vars
		$newLang = t3lib_div::_POST('newLang');

		// write new language file
		try {
			$this->execWrite($newLang);
		} catch (LFException $e) {
			throw new $e;
		}
	}

	/**
	 * code for output generation of function "const.add"
	 *
	 * @param string $constant name of adding constant
	 * @param array $defValues default Values
	 * @return string generated html content
	 */
	private function outputFuncConstAdd($constant, $defValues) {
		// get vars
		$numTextAreaRows = $this->extConfig['numTextAreaRows'];

		// get output
		$languages = tx_lfeditor_mod1_functions::buildLangArray($this->extConfig['viewLanguages']);
		$langArray = array_merge(array('default'), $languages);
		$content = tx_lfeditor_mod1_template::outputAddConst(
			$langArray, $constant,
			$defValues, $numTextAreaRows
		);

		return $content;
	}

	/**
	 * code for all actions of function "const.add"
	 *
	 * @throws LFException raised if constant is empty or already exists or writing of file failed
	 * @param array $langData language array
	 * @param array $newLang new values of each language for the constant
	 * @param string $constant name of constant which should be added
	 * @return void
	 */
	private function actionFuncConstAdd($langData, &$newLang, &$constant) {
		// checks
		if (empty($constant)) {
			throw new LFException('failure.select.noConstDefined');
		}

		if (!empty($langData['default'][$constant])) {
			throw new LFException('failure.langfile.constExists');
		}

		// writing
		try {
			$add = array();
			foreach ($newLang as $lang => $value) {
				$add[$lang][$constant] = $value;
			}

			$this->execWrite($add);
			$constant = '';
			$newLang = array();
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * code for output generation of function "const.delete"
	 *
	 * @throws LFException raised if no constant was selected
	 * @return string generated html content
	 */
	private function outputFuncConstDelete() {
		// get vars
		$constant = $this->MOD_SETTINGS['constList'];

		// checks
		if (empty($constant) || $constant == '###default###') {
			throw new LFException('failure.select.noConst', 1);
		}

		// get output
		$content = tx_lfeditor_mod1_template::outputDeleteConst($constant);

		return $content;
	}

	/**
	 * code for all actions of function "const.delete"
	 *
	 * @throws LFException raised if the language file couldnt be written
	 * @return void
	 */
	private function actionFuncConstDelete() {
		// get vars
		$constant = $this->MOD_SETTINGS['constList'];
		$delAllLang = t3lib_div::_POST('delAllLang');

		// write new language file
		try {
			// get languages
			if ($delAllLang) {
				$languages = tx_lfeditor_mod1_functions::buildLangArray();
				$langArray = array_merge(array('default'), $languages);
			} else {
				$langArray =
					tx_lfeditor_mod1_functions::buildLangArray($this->extConfig['viewLanguages']);
			}

			// build modArray
			$newLang = array();
			foreach ($langArray as $lang) {
				$newLang[$lang][$constant] = '';
			}

			$this->execWrite($newLang, array(), TRUE);
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * code for output generation of function "const.rename"
	 *
	 * @throws LFException raised if no constant was selected
	 * @return string generated html content
	 */
	private function outputFuncConstRename() {
		// get vars
		$constant = $this->MOD_SETTINGS['constList'];

		// checks
		if (empty($constant) || $constant == '###default###') {
			throw new LFException('failure.select.noConst', 1);
		}

		// get output
		$content = tx_lfeditor_mod1_template::outputRenameConst($constant);

		return $content;
	}

	/**
	 * code for all actions of function "const.rename"
	 *
	 * @throws LFException raised if the language file could not be written
	 * @param array language array
	 * @return void
	 */
	private function actionFuncConstRename($langData) {
		// get vars
		$oldConst = $this->MOD_SETTINGS['constList'];
		$newConst = t3lib_div::_POST('newConst');

		if ($oldConst === $newConst) {
			throw new LFException('failure.langfile.noChange');
		}

		if (!empty($langData['default'][$newConst])) {
			throw new LFException('failure.langfile.constExists');
		}

		// write new language file
		try {
			// get languages
			$langArray = array_merge(array('default'), tx_lfeditor_mod1_functions::buildLangArray());

			// build modArray
			$newLang = array();
			foreach ($langArray as $lang) {
				if (isset($langData[$lang][$oldConst])) {
					$newLang[$lang][$newConst] = $langData[$lang][$oldConst];
				}

				$newLang[$lang][$oldConst] = '';
			}

			$this->execWrite($newLang, array(), TRUE);
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * code for output generation of function "const.search"
	 *
	 * @throws LFException raised if nothing found or a empty search string is given
	 * @param array language array
	 * @return string generated html content
	 */
	private function outputFuncConstSearch($langData) {
		// get vars
		$searchStr = t3lib_div::_POST('searchStr');
		$caseSensitive = t3lib_div::_POST('caseSensitive');
		$searchOptions = $caseSensitive ? '' : 'i';

		// search
		$resultArray = array();
		$preMsg = NULL;
		if (!preg_match('/^\/.*\/.*$/', $searchStr) && !empty($searchStr)) {
			foreach ($langData as $langKey => $data) {
				if (is_array($data)) {
					foreach ($data as $labelKey => $labelValue) {
						if (preg_match('/' . $searchStr . '/' . $searchOptions, $labelValue)) {
							$resultArray[$langKey][$labelKey] = $labelValue;
						}
					}
				}
			}
			if (!count($resultArray)) {
				$preMsg = new LFException('failure.search.noConstants', 1);
			}
		} else {
			$preMsg = new LFException('function.const.search.enterSearchStr', 1);
		}

		// get output
		$content = tx_lfeditor_mod1_template::outputSearchConst(
			$searchStr, $resultArray,
			(is_object($preMsg) ? $preMsg->getMessage() : ''), $caseSensitive
		);

		return $content;
	}

	/**
	 * code for all actions of function "const.search"
	 *
	 * @return void
	 */
	private function actionFuncConstSearch() {
		// get vars
		$constant = t3lib_div::_POST('constant');

		// redirect
		if (!empty($constant)) {
			header(
				'Location: ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') .
				'?M=user_txlfeditorM1&SET[constList]=' . $constant . '&SET[function]=const.edit'
			);
		}
	}

	/**
	 * code for output generation of function "const.treeview"
	 *
	 * @throws LFException raised if no language data was found in the selected language
	 * @param array $langData language array
	 * @param string $curToken current explode Token
	 * @return string generated html content
	 */
	private function outputFuncConstTreeview($langData, $curToken) {
		// get vars
		$usedLangData = $langData[$this->MOD_SETTINGS['langList']];
		$refLangData = $langData[$this->MOD_SETTINGS['patternList']];
		$treeHide = $this->extConfig['treeHide'];

		// checks
		if (!is_array($usedLangData) || !count($usedLangData)) {
			throw new LFException('failure.select.emptyLanguage', 1);
		}

		// get output
		$tree = tx_lfeditor_mod1_functions::genTreeInfoArray($usedLangData, $refLangData, $curToken);
		$content = tx_lfeditor_mod1_template::outputTreeView($tree, $treeHide);

		return $content;
	}

	/**
	 * code for all actions of function "const.treeview"
	 *
	 * @return void
	 */
	private function actionFuncConstTreeview() {
		// get vars
		$constant = t3lib_div::_POST('constant');

		// redirect
		if (!empty($constant)) {
			header(
				'Location: ' . t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT') .
				'?M=user_txlfeditorM1&SET[constList]=' . $constant . '&SET[function]=const.edit'
			);
		}
	}

	/**
	 * code for output generation of function "backupMgr"
	 *
	 * @throws LFException raised if meta array is empty (no backup files)
	 * @return string generated html content
	 */
	private function outputFuncBackupMgr() {
		// get vars
		$filename = t3lib_div::_POST('file');
		$origDiff = t3lib_div::_POST('origDiff');
		$extPath = $this->MOD_SETTINGS['extList'];

		// get output
		$metaArray = $this->backupObj->getMetaInfos(2);
		if (!count($metaArray)) {
			throw new LFException('failure.backup.noFiles', 1);
		}
		$content = tx_lfeditor_mod1_template::outputManageBackups($metaArray, $extPath);

		$diff = $metaDiff = NULL;
		if ($origDiff) {
			// set backup file
			$metaArray = $this->backupObj->getMetaInfos(3);
			$informations = array(
				'absPath' => typo3Lib::fixFilePath(
					PATH_site . '/' .
					$metaArray[$filename]['pathBackup']
				),
				'relFile' => $filename,
			);
			$this->backupObj->setVar($informations);

			// exec diff
			try {
				// read original file
				$this->initFileObject(
					$this->backupObj->getVar('langFile'),
					PATH_site . '/' . $this->backupObj->getVar('extPath'),
					$this->MOD_SETTINGS['wsList']
				);

				// read backup file
				$this->backupObj->readFile();

				// get language data
				$origLang = $this->fileObj->getLocalLangData();
				$backupLocalLang = $this->backupObj->getLocalLangData();

				// get meta data
				$origMeta = $this->fileObj->getMetaData();
				$backupMeta = $this->backupObj->getMetaData();

				$diff = tx_lfeditor_mod1_functions::getBackupDiff(0, $origLang, $backupLocalLang);
				$metaDiff = tx_lfeditor_mod1_functions::getMetaDiff(0, $origMeta, $backupMeta);
			} catch (LFException $e) {
				return $e->getMessage() . $content;
			}
		}

		// generate diff
		if (count($diff)) {
			$content .= tx_lfeditor_mod1_template::outputManageBackupsDiff(
				$diff, $metaDiff,
				$this->fileObj->getLocalLangData(), $this->backupObj->getLocalLangData(),
				$this->fileObj->getOriginLangData(), $this->backupObj->getOriginLangData(),
				$this->fileObj->getMetaData(), $this->backupObj->getMetaData()
			);
		}

		return $content;
	}

	/**
	 * code for all actions of function "backupMgr"
	 *
	 * @throws LFException raised if a backup file couldnt be deleted or recovered
	 * @return void
	 */
	private function actionFuncBackupMgr() {
		// get vars
		$filename = t3lib_div::_POST('file');
		$restore = t3lib_div::_POST('restore');
		$deleteAll = t3lib_div::_POST('deleteAll');
		$delete = t3lib_div::_POST('delete');

		// exec changes
		try {
			// restore or delete backup files
			if ($restore) {
				// set backup file
				$metaArray = $this->backupObj->getMetaInfos(3);
				$informations = array(
					'absPath' => PATH_site . $metaArray[$filename]['pathBackup'],
					'relFile' => $filename,
				);
				$this->backupObj->setVar($informations);
				$this->backupObj->readFile();

				// read original file
				$this->initFileObject(
					$this->backupObj->getVar('langFile'),
					PATH_site . '/' . $this->backupObj->getVar('extPath'),
					$this->MOD_SETTINGS['wsList']
				);

				// restore
				$this->execBackupRestore();
			} elseif ($deleteAll || $delete) {
				$delFiles = array();
				if ($deleteAll) {
					$metaArray = $this->backupObj->getMetaInfos(2);
					foreach ($metaArray as $langFile => $metaPiece) {
						$files = array_keys($metaPiece);
						foreach ($files as $filename) {
							$delFiles[$filename] = $langFile;
						}
					}
				} else {
					$delFiles[$filename] = '';
				}

				$this->execBackupDelete($delFiles);
			}
		} catch (LFException $e) {
			throw $e;
		}
	}

	/**
	 * generates the module content
	 *
	 * @throws LFException raised if any output failure occurred
	 * @return string
	 */
	private function moduleContent() {
		$moduleContent = '';

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// generate menus
		$sectName = $preContent = $content = '';
		try {
			// generate extension and workspace list
			$name = 'select.extensionAndWorkspace';
			$name = tx_lfeditor_mod1_functions::prepareSectionName($name);
			$moduleContent .= $this->doc->section($name, '', 0, 1);
			$this->menuExtList();
			$extList = $this->getFuncMenu('extList');
			$this->menuWorkspaceList();
			$moduleContent .= $this->doc->funcMenu($extList . $this->getFuncMenu('wsList'), '');

			// generate language file list
			if ($this->MOD_SETTINGS['function'] != 'backupMgr') {
				$name = tx_lfeditor_mod1_functions::prepareSectionName('select.langfile');
				$moduleContent .= $this->doc->section($name, '', 0, 1);
				$this->menuLangFileList();
				$moduleContent .= $this->doc->funcMenu($this->getFuncMenu('langFileList'), '');
			}
		} catch (LFException $e) {
			$e->setGeneratedContent($moduleContent);
			throw $e;
		}

		// init language file object
		try {
			if ($this->MOD_SETTINGS['function'] != 'backupMgr') {
				$this->initFileObject(
					$this->MOD_SETTINGS['langFileList'],
					$this->MOD_SETTINGS['extList'], $this->MOD_SETTINGS['wsList']
				);
			}
		} catch (LFException $e) {
			$e->setGeneratedContent($moduleContent);
			throw $e;
		}

		// init backup object
		try {
			if ($this->MOD_SETTINGS['function'] == 'backupMgr') {
				$informations = array(
					'extPath' => sgLib::trimPath(PATH_site, $this->MOD_SETTINGS['extList']),
					'langFile' => t3lib_div::_POST('langFile'),
				);
				$this->initBackupObject($this->MOD_SETTINGS['wsList'], $informations);
			}
		} catch (LFException $e) {
			$e->setGeneratedContent($moduleContent);
			throw $e;
		}

		// generate general output
		switch ($this->MOD_SETTINGS['function']) {
			case 'general':
				// exec action specific part of function
				try {
					$submit = t3lib_div::_POST('submitted');
					$sendMail = t3lib_div::_POST('sendMail');
					if ($submit) {
						if ($this->actionFuncGeneral()) {
							if (!$sendMail) {
								$preContent = '<p class="tx-lfeditor-success">' .
									$lang->getLL('lang.file.write.success') . '</p>';
							} else {
								$preContent = '<p class="tx-lfeditor-success">' .
									$lang->getLL('function.general.mail.success') .
									'</p>';
							}
						}
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the language reference list
				$this->menuLangList($langData, 'patternList');
				$refMenu = $this->doc->funcMenu($this->getFuncMenu('patternList'), '');
				$sectName = 'select.referenceLanguage';
				$name = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				$moduleContent .= $this->doc->section($name, $refMenu, 0, 1);

				// get main content
				$content = $this->outputFuncGeneral();
				$sectName = 'function.general.general';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'langfile.edit':
				// start session
				session_start();

				// exec action specific part of function
				try {
					$submit = t3lib_div::_POST('submitted');
					$session = t3lib_div::_POST('session');
					if ($submit) {
						$this->actionFuncLangfileEdit();
						if (!$session) {
							$preContent = '<p class="tx-lfeditor-success">' .
								$lang->getLL('lang.file.write.success') . '</p>';
						}
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the language- and pattern list
				$this->menuLangList($langData, 'langList');
				$langList = $this->getFuncMenu('langList');

				$this->menuLangList(
					$langData, 'langfileEditPatternList',
					$lang->getLL('select.nothing')
				);
				$patternList = $this->getFuncMenu('langfileEditPatternList');

				$languageMenu = $this->doc->funcMenu($langList . $patternList, '');
				$name = 'select.languageAndPattern';
				$name = tx_lfeditor_mod1_functions::prepareSectionName($name);
				$moduleContent .= $this->doc->section($name, $languageMenu, 0, 1);

				// draw type selector
				$this->menuConstantType();
				$typeList = $this->getFuncMenu('constTypeList');

				$typeMenu = $this->doc->funcMenu($typeList, '');
				$name = 'select.constantType';
				$name = tx_lfeditor_mod1_functions::prepareSectionName($name);
				$moduleContent .= $this->doc->section($name, $typeMenu, 0, 1);

				// get main content
				try {
					$content = $this->outputFuncLangfileEdit($langData);
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.langfile.edit';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.edit':
				// exec action specific part of function
				try {
					$submit = t3lib_div::_POST('submit');
					if ($submit) {
						$this->actionFuncConstEdit();
						$preContent = '<p class="tx-lfeditor-success">' .
							$lang->getLL('lang.file.write.success') . '</p>';
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the constant list menu
				$this->menuConstList($langData, $lang->getLL('select.nothing'));
				$constList = $this->doc->funcMenu($this->getFuncMenu('constList'), '');
				$name = tx_lfeditor_mod1_functions::prepareSectionName('select.constant');
				$moduleContent .= $this->doc->section($name, $constList, 0, 1);

				// get main content
				try {
					$content = $this->outputFuncConstEdit($langData);
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.edit.edit';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.add':
				$constant = t3lib_div::_POST('nameOfConst');
				$newLang = t3lib_div::_POST('newLang');
				$langData = $this->fileObj->getLocalLangData();

				// exec action specific part of function
				$submit = t3lib_div::_POST('submit');
				try {
					if ($submit) {
						$this->actionFuncConstAdd($langData, $newLang, $constant);
						$preContent = '<p class="tx-lfeditor-success">' .
							$lang->getLL('lang.file.write.success') . '</p>';
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get main content
				try {
					$content = $this->outputFuncConstAdd($constant, $newLang);
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.add.add';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.delete':
				// exec action specific part of function
				try {
					$submit = t3lib_div::_POST('submit');
					if ($submit) {
						$this->actionFuncConstDelete();
						$preContent = '<p class="tx-lfeditor-success">' .
							$lang->getLL('lang.file.write.success') . '</p>';
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the constant list menu
				$this->menuConstList($langData, $lang->getLL('select.nothing'));
				$constList = $this->doc->funcMenu($this->getFuncMenu('constList'), '');
				$name = tx_lfeditor_mod1_functions::prepareSectionName('select.constant');
				$moduleContent .= $this->doc->section($name, $constList, 0, 1);

				// get main content
				try {
					$content = $this->outputFuncConstDelete();
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.delete.delete';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.rename':
				// exec action specific part of function
				try {
					$submit = t3lib_div::_POST('submit');
					if ($submit) {
						$langData = $this->fileObj->getLocalLangData();
						$this->actionFuncConstRename($langData);
						$preContent = '<p class="tx-lfeditor-success">' .
							$lang->getLL('lang.file.write.success') . '</p>';
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the constant list menu
				$this->menuConstList($langData, $lang->getLL('select.nothing'));
				$constList = $this->doc->funcMenu($this->getFuncMenu('constList'), '');
				$name = tx_lfeditor_mod1_functions::prepareSectionName('select.constant');
				$moduleContent .= $this->doc->section($name, $constList, 0, 1);

				// get main content
				try {
					$content = $this->outputFuncConstRename();
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.rename.rename';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.search':
				// exec action specific part of function
				$submit = t3lib_div::_POST('submitted');
				if ($submit) {
					$this->actionFuncConstSearch();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// get main content
				try {
					$content = $this->outputFuncConstSearch($langData);
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.search.search';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'const.treeview':
				$curToken = t3lib_div::_POST('usedToken');

				// exec action specific part of function
				$submit = t3lib_div::_POST('submitted');
				if ($submit) {
					$this->actionFuncConstTreeview();
				}

				// get language data
				$langData = $this->fileObj->getLocalLangData();

				// draw the language and reference list
				$this->menuLangList($langData, 'langList');
				$langList = $this->getFuncMenu('langList');

				$this->menuLangList($langData, 'patternList');
				$refList = $this->getFuncMenu('patternList');

				$name = 'select.languageAndPattern';
				$name = tx_lfeditor_mod1_functions::prepareSectionName($name);
				$langMenu = $this->doc->funcMenu($langList . $refList, '');
				$moduleContent .= $this->doc->section($name, $langMenu, 0, 1);

				// draw explode token menu
				$curToken = tx_lfeditor_mod1_functions::getExplodeToken(
					$curToken,
					$langData[$this->MOD_SETTINGS['patternList']]
				);
				$selToken = tx_lfeditor_mod1_template::fieldSetToken($curToken);
				$treeMenu = $this->doc->funcMenu($selToken, '');
				$name = 'select.explodeToken';
				$name = tx_lfeditor_mod1_functions::prepareSectionName($name);
				$moduleContent .= $this->doc->section($name, $treeMenu, 0, 1);

				// get main content
				try {
					$content = $this->outputFuncConstTreeview($langData, $curToken);
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.const.treeview.treeview';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;

			case 'backupMgr':
				// exec action specific part of function
				try {
					$origDiff = t3lib_div::_POST('origDiff');
					$submit = t3lib_div::_POST('submitted');
					if ($submit) {
						$this->actionFuncBackupMgr();
						if (!$origDiff) {
							$preContent = '<p class="tx-lfeditor-success">' .
								$lang->getLL('function.backupMgr.success') . '</p>';
						}
					}
				} catch (LFException $e) {
					$preContent = $e->getMessage();
				}

				// get main content
				try {
					$content = $this->outputFuncBackupMgr();
				} catch (LFException $e) {
					$content = $e->getMessage();
				}
				$sectName = 'function.backupMgr.backupMgr';
				$sectName = tx_lfeditor_mod1_functions::prepareSectionName($sectName);
				break;
		}

		// save generated content
		$moduleContent .= $this->doc->section($sectName, $preContent . $content, 0, 1);

		return $moduleContent;
	}
}

// Default-Code for using XCLASS (dont touch)
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/index.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/index.php']);
}

/** @var $SOBE tx_lfeditor_module1 */
$SOBE = t3lib_div::makeInstance('tx_lfeditor_module1');
$SOBE->main();
$SOBE->printContent();

?>