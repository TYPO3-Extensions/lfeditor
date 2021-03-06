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

/** general file functions */
require_once(t3lib_extMgm::extPath('lfeditor') . 'mod1/class.tx_lfeditor_mod1_file.php');

/**
 * xll workspace class
 */
abstract class tx_lfeditor_mod1_file_xll extends tx_lfeditor_mod1_file {
	/**
	 * @var string
	 */
	protected $typo3RelFile;

	/**
	 * @return mixed
	 */
	abstract protected function readLLFile();

	/**
	 * extended init
	 *
	 * @param string $file name of the file (can be a path, if you need this (no check))
	 * @param string $path path to the file
	 * @param string $typo3RelFile relative typo3 path to an language file (i.e. EXT:lfeditor/mod1/locallang.xml)
	 * @return void
	 */
	public function init($file, $path, $typo3RelFile) {
		// set vars
		$this->setVar(array('workspace' => 'xll', 'typo3RelFile' => $typo3RelFile));
		parent::init($file, $path);
	}

	/**
	 * sets information
	 *
	 * structure:
	 * $infos["typo3RelFile"] = relative path with filename from "absPath"
	 *
	 * @param array $informations
	 * @return void
	 */
	public function setVar($informations) {
		if (!empty($informations['typo3RelFile'])) {
			$this->typo3RelFile = typo3Lib::fixFilePath($informations['typo3RelFile']);
		}

		parent::setVar($informations);
	}

	/**
	 * returns requested information
	 *
	 * @param string $info
	 * @return mixed
	 */
	public function getVar($info) {
		if ($info == 'typo3RelFile') {
			return $this->typo3RelFile;
		} else {
			return parent::getVar($info);
		}
	}

	/**
	 * reads the absolute file
	 *
	 * @throws LFException raised if no language content could not fetched
	 * @return void
	 */
	public function readFile() {
		// read absolute file
		try {
			$localLang = $this->readLLFile();
		} catch (LFException $e) {
			throw $e;
		}

		// set origin array and sort language contents
		$languages = sgLib::getSystemLanguages();
		$originLang = array();
		foreach ($languages as $lang) {
			$originLang[$lang] = $this->absFile;
			if (is_array($localLang[$lang]) && count($localLang[$lang])) {
				ksort($localLang[$lang]);
			}
		}

		// copy all to object variables, if everything went ok
		$this->localLang = $localLang;
		$this->originLang = $originLang;
	}

	/**
	 * extends writing of language files for xll
	 *
	 * @throws Exception|LFException
	 * @throws LFException
	 * @return bool|void always true
	 */
	public function writeFile() {
		// create xll directory
		try {
			sgLib::createDir($this->absPath, PATH_site);
		} catch (Exception $e) {
			throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
		}

		// write xll file
		try {
			parent::writeFile();
		} catch (LFException $e) {
			throw $e;
		}

		// set only new values in localconf if something changed
		$relXLLFile = sgLib::trimPath(PATH_site, $this->absFile);
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$this->typo3RelFile] == $relXLLFile) {
			return TRUE;
		}

		try {
			$fileRef = substr($this->typo3RelFile, 0, strrpos($this->typo3RelFile, '.'));

			$addLine = '$TYPO3_CONF_VARS[\'BE\'][\'XLLfile\'][\'' . $fileRef . '.xml\']';
			typo3Lib::writeLocalconf($addLine, $relXLLFile);
			$GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$fileRef . '.xml'] = $relXLLFile;

			// create alternative
			$addLine = '$TYPO3_CONF_VARS[\'BE\'][\'XLLfile\'][\'' . $fileRef . '.php\']';
			typo3Lib::writeLocalconf($addLine, $relXLLFile);
			$GLOBALS['TYPO3_CONF_VARS']['BE']['XLLfile'][$fileRef . '.php'] = $relXLLFile;
		} catch (Exception $e) {
			throw new LFException('failure.failure', 0, '(' . $e->getMessage() . ')');
		}

		return TRUE;
	}
}

// Default-Code for using XCLASS (dont touch)
if (defined(
		'TYPO3_MODE'
	) && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_file_xll.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_file_xll.php']);
}

?>