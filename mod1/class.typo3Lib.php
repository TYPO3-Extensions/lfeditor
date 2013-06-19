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
 * includes special typo3 methods
 */
class typo3Lib {
	const pathLocalExt = 'typo3conf/ext/';

	const pathGlobalExt = 'typo3/ext/';

	const pathSysExt = 'typo3/sysext/';

	const pathL10n = 'typo3conf/l10n/';

	/**
	 * checks the file location type
	 *
	 * @param string $file
	 * @return string
	 */
	public static function checkFileLocation($file) {
		if (strpos($file, typo3Lib::pathLocalExt) !== FALSE) {
			return 'local';
		} elseif (strpos($file, typo3Lib::pathGlobalExt) !== FALSE) {
			return 'global';
		} elseif (strpos($file, typo3Lib::pathSysExt) !== FALSE) {
			return 'system';
		} elseif (strpos($file, typo3Lib::pathL10n) !== FALSE) {
			return 'l10n';
		} else {
			return '';
		}
	}

	/**
	 * converts an absolute or relative typo3 style (EXT:) file path
	 *
	 * @throws Exception raised, if the conversion fails
	 * @param string $file absolute file or an typo3 relative file (EXT:)
	 * @param boolean $mode generate to relative(false) or absolute file
	 * @return string converted file path
	 */
	public static function transTypo3File($file, $mode) {
		$extType['local'] = typo3Lib::pathLocalExt;
		$extType['global'] = typo3Lib::pathGlobalExt;
		$extType['system'] = typo3Lib::pathSysExt;

		// relative to absolute
		if ($mode) {
			if (strpos($file, 'EXT:') === FALSE) {
				throw new Exception('no typo3 relative path "' . $file . '"');
			}

			$cleanFile = sgLib::trimPath('EXT:', $file);
			foreach ($extType as $type) {
				$path = typo3Lib::fixFilePath(PATH_site . '/' . $type . '/' . $cleanFile);
				if (is_dir(dirname($path))) {
					return $path;
				}
			}

			throw new Exception('cant convert typo3 relative file "' . $file . '"');
		} else // absolute to relative
		{
			foreach ($extType as $type) {
				if (strpos($file, $type) === FALSE) {
					continue;
				}

				return 'EXT:' . sgLib::trimPath($type, sgLib::trimPath(PATH_site, $file));
			}

			throw new Exception('cant convert absolute file "' . $file . '"');
		}
	}

	/**
	 * generates portable file paths
	 *
	 * @param string $file file
	 * @return string fixed file
	 */
	public static function fixFilePath($file) {
		return t3lib_div::fixWindowsFilePath(str_replace('//', '/', $file));
	}

	/**
	 * writes the localconf file
	 *
	 * @throws Exception raised if localconf is empty or cant be backuped
	 * @param string $addLine line which should be added
	 * @param string $value value of line
	 * @return void
	 */
	public static function writeLocalconf($addLine, $value) {
		$localconf = PATH_typo3conf . 'localconf.php';

		// get current content
		$lines = file_get_contents($localconf);
		if (empty($lines)) {
			throw new Exception('localconf is empty...');
		}
		$lines = explode("\n", str_replace('?>', '', $lines));
		/** @var $localConfObj t3lib_install */
		$localConfObj = t3lib_div::makeInstance('t3lib_install');
		$localConfObj->updateIdentity = 'LFEditor';

		// add information
		$localConfObj->setValueInLocalconfFile($lines, $addLine, $value);

		// backup localconf
		if (!copy($localconf, $localconf . '.bak.php')) {
			throw new Exception('localconf couldnt be backuped...');
		}

		// write localconf
		$localConfObj->allowUpdateLocalConf = 1;
		$localConfObj->writeToLocalconf_control($lines);
	}

	/**
	 * decodes or encodes all values in the given language array to utf-8
	 *
	 * @param array $localLang language content array
	 * @param boolean $mode to utf-8 (true) or to original charset (false)
	 * @param array $ignoreKeys language keys to ignore
	 * @return array decoded or encoded language content array
	 */
	public static function utf8($localLang, $mode, $ignoreKeys) {
		// check
		if (!is_array($localLang) || !count($localLang)) {
			return $localLang;
		}

		// get charset object
		/** @var $csConvObj t3lib_cs */
		$csConvObj = & $GLOBALS['LANG']->csConvObj;

		// loop all possible languages
		foreach ($localLang as $langKey => $convContent) {
			if (!is_array($convContent) || !count($convContent) || in_array($langKey, $ignoreKeys)) {
				continue;
			}

			$origCharset = $csConvObj->parse_charset(
				$csConvObj->charSetArray[$langKey] ?
					$csConvObj->charSetArray[$langKey] : 'iso-8859-1'
			);

			if ($csConvObj->charSetArray[$langKey] == 'utf-8') {
				continue;
			}

			foreach ($convContent as $labelKey => $value) {
				if ($mode) {
					$localLang[$langKey][$labelKey] = $csConvObj->utf8_encode($value, $origCharset);
				} else {
					$localLang[$langKey][$labelKey] = $csConvObj->utf8_decode($value, $origCharset);
				}
			}
		}

		return $localLang;
	}

	/**
	 * Returns true if the TYPO3 backend is UTF-8 ready.
	 *
	 * @static
	 * @return bool
	 */
	public static function isTypo3BackendInUtf8Mode() {
		$isInUtf8Mode = FALSE;
		$isTypo347 = (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 4007000);
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] === 'utf-8' || $isTypo347) {
			$isInUtf8Mode = TRUE;
		}

		return $isInUtf8Mode;
	}
}

// Default-Code for using XCLASS (dont touch)
if (defined(
		'TYPO3_MODE'
	) && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.typo3Lib.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.typo3Lib.php']);
}

?>