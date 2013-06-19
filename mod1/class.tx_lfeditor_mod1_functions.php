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

/**
 * contains functions for the 'lfeditor' extension
 */
class tx_lfeditor_mod1_functions {
	/**
	 * prepares the extension array for MOD_MENU
	 *
	 * This function creates the surface of the select box and adds
	 * some additional information to each entry.
	 *
	 * Structure of file array:
	 * $fileArray[textHeader] = further arrays with extension paths
	 *
	 * @param array $fileArray see above
	 * @return array prepared array
	 */
	public static function prepareExtList($fileArray) {
		// init vars
		$myArray = array();

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// prepareExtensions
		$numHeader = count($fileArray);
		foreach ($fileArray as $header => $extPaths) {
			if (!is_array($extPaths) || !count($extPaths)) {
				continue;
			}

			unset($prepArray);
			foreach ($extPaths as $extPath) {
				intval(t3lib_extmgm::isLoaded(basename($extPath))) ?
					$state = $lang->getLL('ext.loaded') :
					$state = $lang->getLL('ext.notLoaded');

				$prepArray[$extPath] = basename($extPath) . ' [' . $state . ']';
			}
			ksort($prepArray);

			// merge arrays
			$myArray = array_merge($myArray, array($header, '---'), $prepArray);

			// add newline, if more than one header line exist
			if ($numHeader-- > 1) {
				$myArray[] = '&nbsp;';
			}
		}

		return $myArray;
	}

	/**
	 * searches extensions in a given path
	 *
	 * Modes for $state:
	 * 0 - loaded and unloaded
	 * 1 - only loaded
	 * 2 - only unloaded
	 *
	 * @throws Exception raised, if the given path cant be opened for reading
	 * @param string $path path
	 * @param integer $state optional: extension state to ignore (see above)
	 * @param string $extIgnoreRegExp optional: directories to ignore (regular expression; pcre with slashes)
	 * @return array result of the search
	 */
	public static function searchExtensions($path, $state = 0, $extIgnoreRegExp = '') {
		if (!@$fhd = opendir($path)) {
			throw new Exception('cant open "' . $path . '"');
		}

		$extArray = array();
		while ($extDir = readdir($fhd)) {
			$extDirPath = $path . '/' . $extDir;

			// ignore all unless the file is a directory and no point dir
			if (!is_dir($extDirPath) || preg_match('/^\.{1,2}$/', $extDir)) {
				continue;
			}

			// check, if the directory/extension should be saved
			if (preg_match($extIgnoreRegExp, $extDir)) {
				continue;
			}

			// state filter
			if ($state) {
				$extState = intval(t3lib_extmgm::isLoaded($extDir));
				if (($extState && $state == 2) || (!$extState && $state == 1)) {
					continue;
				}
			}

			$extArray[] = $extDirPath;
		}
		closedir($fhd);

		return $extArray;
	}

	/**
	 * prepares a given language string for section output
	 *
	 * @param string language string
	 * @return string prepared output in sections
	 */
	public static function prepareSectionName($value) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		return html_entity_decode($lang->getLL($value));
	}

	/**
	 * checks and returns given languages or TYPO3 language list if the given content was empty
	 *
	 * @param array $languages optional: some language shortcuts
	 * @return array language list
	 */
	public static function buildLangArray($languages = NULL) {
		if (!is_array($languages) || !count($languages)) {
			return sgLib::getSystemLanguages();
		} else {
			return $languages;
		}
	}

	/**
	 * generates output for a diff between the backup and original file
	 *
	 * Note that the generated diff will be an array with a normal structure like
	 * any language content array.
	 *
	 * Modes of diffType:
	 * - all changes at the original since the backup was done (0)
	 * - only changes at the original (1)
	 * - only changes at the backup (2)
	 *
	 * @param integer $diffType see above for available modes
	 * @param array $origLang original language data
	 * @param array $backupLocalLang backup language data
	 * @return mixed generated diff
	 */
	public static function getBackupDiff($diffType, $origLang, $backupLocalLang) {
		// get all languages and generate the diff
		$langKeys = array_merge(array_keys($origLang), array_keys($backupLocalLang));
		$diff = array();
		foreach ($langKeys as $langKey) {
			// prevent warnings
			if (!is_array($origLang[$langKey])) {
				$origLang[$langKey] = array();
			}
			if (!is_array($backupLocalLang[$langKey])) {
				$backupLocalLang[$langKey] = array();
			}
			$origDiff[$langKey] = array();
			$backupDiff[$langKey] = array();

			// generate diff
			if (!$diffType || $diffType == 1) {
				$origDiff[$langKey] = array_diff_assoc($origLang[$langKey], $backupLocalLang[$langKey]);
			}
			if (!$diffType || $diffType == 2) {
				$backupDiff[$langKey] = array_diff_assoc(
					$backupLocalLang[$langKey],
					$origLang[$langKey]
				);
			}
			$diff[$langKey] = array_merge($origDiff[$langKey], $backupDiff[$langKey]);
		}
		return $diff;
	}

	/**
	 * generates output for a meta diff between the backup and original file
	 *
	 * Note that the generated diff will be an array with a normal structure like
	 * any meta content array.
	 *
	 * Modes of diffType:
	 * - all changes at the original since the backup was done (0)
	 * - only changes at the original (1)
	 * - only changes at the backup (2)
	 *
	 * @param integer $diffType see above for available modes
	 * @param array $origMeta original meta data
	 * @param array $backupMeta backup meta data
	 * @return mixed generated diff
	 */
	public static function getMetaDiff($diffType, $origMeta, $backupMeta) {
		$origDiff = array();
		$backupDiff = array();

		if (!$diffType || $diffType == 1) {
			$origDiff = array_diff_assoc($origMeta, $backupMeta);
		}
		if (!$diffType || $diffType == 2) {
			$backupDiff = array_diff_assoc($backupMeta, $origMeta);
		}

		if ($diffType == 1) {
			return $origDiff;
		} elseif ($diffType == 2) {
			return $backupDiff;
		} else {
			return array_merge($origDiff, $backupDiff);
		}
	}

	/**
	 * generates a general information array
	 *
	 * @param string $refLang reference language
	 * @param array $languages language key array
	 * @param tx_lfeditor_mod1_file_basePHP $fileObj file object
	 * @return array general information array
	 * @see outputGeneral()
	 */
	public static function genGeneralInfoArray($refLang, $languages, $fileObj) {
		// reference language data information
		$localRefLangData = $fileObj->getLocalLangData($refLang);

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// generate needed data
		$infos = array();
		foreach ($languages as $langKey) {
			// get origin data and meta information
			$origin = $fileObj->getOriginLangData($langKey);
			$infos['default']['meta'] = $fileObj->getMetaData();

			// language data
			$localLangData = $fileObj->getLocalLangData($langKey);

			// detailed constants information
			$infos[$langKey]['numUntranslated'] =
				count(array_diff_key($localRefLangData, $localLangData));
			$infos[$langKey]['numUnknown'] =
				count(array_diff_key($localLangData, $localRefLangData));
			$infos[$langKey]['numTranslated'] =
				count(array_intersect_key($localLangData, $localRefLangData));

			// get location type
			if ($fileObj->getVar('workspace') != 'xll') {
				$locType = typo3Lib::checkFileLocation($origin);
				if ($locType == 'local') {
					$infos[$langKey]['type'] = $lang->getLL('ext.local');
				} elseif ($locType == 'global') {
					$infos[$langKey]['type'] = $lang->getLL('ext.global');
				} elseif ($locType == 'system') {
					$infos[$langKey]['type'] = $lang->getLL('ext.system');
				} elseif ($locType == 'l10n') {
					$infos[$langKey]['type'] = $lang->getLL('lang.file.l10n');
					$infos[$langKey]['type2'] = 'l10n';
				} else {
					$infos[$langKey]['type'] = $lang->getLL('ext.unknown');
				}

				if ($infos[$langKey]['type2'] != 'l10n') {
					if ($fileObj->checkLocalizedFile(basename($origin), $langKey)) {
						$infos[$langKey]['type2'] = 'splitted';
					} else {
						$infos[$langKey]['type2'] = 'merged';
					}
				}
			} else {
				$infos[$langKey]['type'] = 'xll';
				$infos[$langKey]['type2'] = 'merged';
			}

			// set origin
			try {
				$infos[$langKey]['origin'] = '[-]';
				if (!empty($origin)) {
					$infos[$langKey]['origin'] = typo3Lib::transTypo3File($origin, FALSE);
				}
			} catch (Exception $e) {
				$infos[$langKey]['origin'] = sgLib::trimPath(PATH_site, $origin);
			}
		}

		return $infos;
	}

	/**
	 * generates a tree information array
	 *
	 * structure:
	 * tree[dimension][branch]['name'] = name of constant
	 * tree[dimension][branch]['type'] = type of constant (0=>normal;1=>untranslated;2=>unknown)
	 * tree[dimension][branch]['parent'] = parentOfBranch (absConstName)
	 * tree[dimension][branch]['childs'] = amount of children
	 *
	 * @param array $langData language data (only one language)
	 * @param array $refLang reference data (only reference language)
	 * @param string $expToken explode token
	 * @return array tree information array
	 */
	public static function genTreeInfoArray($langData, $refLang, $expToken) {
		// reference language
		$refConsts = array();
		if (is_array($refLang) && count($refLang)) {
			$refConsts = array_keys($refLang);
		}
		$langConsts = array_merge(array_keys($langData), $refConsts);

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// generate tree information array
		$curAbsName = '';
		$tree = array();
		foreach ($langConsts as $constant) {
			// add root
			$rootLabel = $lang->getLL('function.const.treeview.root');
			$tree[0][$rootLabel]['name'] = $rootLabel;

			// get type
			$type = 0; // normal
			if (!in_array($constant, $refConsts)) {
				$type = 2;
			} // unknown
			elseif (empty($langData[$constant])) {
				$type = 1;
			} // untranslated

			$branches = explode($expToken, $constant);
			$numBranches = count($branches);
			for ($i = 0, $curDim = 1; $i < $numBranches; ++$i, ++$curDim) {
				// get current absolute constant name
				if (!$i) {
					$curAbsName = $branches[$i];
				} else {
					$curAbsName .= $expToken . $branches[$i];
				}

				if (isset($tree[$curDim][$curAbsName]['name'])) {
					continue;
				}

				// add branch
				$tree[$curDim][$curAbsName]['name'] = $branches[$i];
				$tree[$curDim][$curAbsName]['type'] = $type;

				// set parent
				if ($i > 0) {
					$parentAbsName = substr($curAbsName, 0, strrpos($curAbsName, $expToken));
				} else {
					$parentAbsName = $rootLabel;
				}

				$tree[$curDim][$curAbsName]['parent'] = $parentAbsName;
				++$tree[$curDim - 1][$parentAbsName]['childs'];
			}
		}

		return $tree;
	}

	/**
	 * get best explode token of a given language data
	 *
	 * @param string $curToken current token
	 * @param array $langData some test language data
	 * @return string new token
	 */
	public static function getExplodeToken($curToken, $langData) {
		// get current token
		if (!empty($curToken)) {
			return $curToken;
		}

		// return default token, if no test data found
		if (!is_array($langData) || !count($langData)) {
			return '.';
		}

		// get ascii codes (possible explode values)
		$ascii['.'] = ord('.');
		$ascii['_'] = ord('_');

		// get best possible character of the default language
		$defKeys = array_keys($langData);
		$numKeys = count($defKeys);
		$maxTestCount = ($numKeys >= 10) ? 10 : $numKeys;
		$counts = array();
		for ($i = 0; $i < $maxTestCount; ++$i) {
			$curCounts = count_chars($defKeys[$i], 1);
			foreach ($ascii as $sign) {
				$counts[$sign] += $curCounts[$sign];
			}
		}

		// get curToken
		foreach ($counts as $sign => $curCounts) {
			if ($curCounts > $counts[$curToken]) {
				$curToken = $sign;
			}
		}

		return chr($curToken);
	}
}

// Default-Code for using XCLASS (dont touch)
if (defined(
		'TYPO3_MODE'
	) && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_functions.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_functions.php']);
}

?>