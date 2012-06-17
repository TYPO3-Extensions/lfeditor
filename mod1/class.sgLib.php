<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2012 Stefan Galinski (stefan.galinski@gmail.com)
 *  All rights reserved
 *
 *  The script is free software; you can redistribute it and/or modify
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
 * personal library with lots of useful methods
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package sgLib
 */
class sgLib {
	###############################
	######## http functions #######
	###############################

	/**
	 * forces download of a file
	 *
	 * @param string $file download file or data string
	 * @param string $filename download filename
	 * @param string $type type of file
	 * @return void
	 */
	public static function download($file, $filename, $type = 'x-type/octtype') {
		if (is_file($file)) {
			$content = readfile($file);
		} else {
			$content = $file;
		}

		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public'); // needed for IE
		header('Content-Type: ' . $type);
		header('Content-Length: ' . strlen($content));
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		echo $content;
	}

	/**
	 * sends email with php multibyte functions
	 *
	 * @throws Exception raised if something failed
	 * @param string $subject subject
	 * @param string $text text
	 * @param string $fromAddress from header
	 * @param string $toAddress email address
	 * @param string $attachement file attachement name or data string (optional)
	 * @param string $sendFileName send filename (optional)
	 * @param string $mbLanguage language (default == unicode)
	 * @return void
	 */
	public static function sendMail($subject, $text, $fromAddress, $toAddress,
									$attachement = '', $sendFileName = '', $mbLanguage = 'uni') {
		// checks
		if (!preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $toAddress) &&
			!preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',
				$toAddress)
		) {
			throw new Exception('email address isnt valid: ' . $toAddress);
		}

		if (!preg_match('/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/', $fromAddress) &&
			!preg_match('/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?)$/',
				$fromAddress)
		) {
			throw new Exception('email address isnt valid: ' . $fromAddress);
		}

		// prepare data
		$text = htmlspecialchars($text);
		$subject = htmlspecialchars($subject);
		if (is_file($attachement)) {
			$fileContent = readfile($attachement);
		} else {
			$fileContent = $attachement;
		}

		// prepare header
		$boundary = md5(uniqid(time()));
		$header = 'From: ' . $fromAddress . "\r\n" . 'X-Mailer: PHP/' . phpversion() . "\r\n";
		if (!empty($fileContent)) {
			$header .= 'MIME-Version: 1.0' . "\r\n";
			$header .= 'Content-Type: multipart/mixed; boundary=' . $boundary . "\r\n\r\n";
			$header .= '--' . $boundary . "\r\n";
			$header .= 'Content-Type: text/plain' . "\r\n";
			$header .= 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n";
			$header .= $text . "\r\n";
			$header .= '--' . $boundary . "\r\n";
			$header .= 'Content-Type: Application/Octet-Stream; name=' . $sendFileName . "\r\n";
			$header .= 'Content-Transfer-Encoding: base64' . "\r\n";
			$header .= 'Content-Disposition: attachment; filename=' . $sendFileName . "\r\n\r\n";
			$header .= chunk_split(base64_encode($fileContent));
			$header .= "\r\n";
			$header .= '--' . $boundary . '--';

			$text = '';
		}

		// send mail
		if (!mb_language($mbLanguage)) {
			throw new Exception('mb_language reported an error: "' . $mbLanguage . '"');
		}
		if (!mb_send_mail($toAddress, $subject, $text, $header)) {
			throw new Exception('mail couldnt be sended to: ' . $toAddress);
		}
	}

	#################################
	######## string functions #######
	#################################

	/**
	 * trims some string from an given path
	 *
	 * @param string $replace string part to delete
	 * @param string $path some path
	 * @param string $prefix some prefix for the new path
	 * @return string new path
	 */
	public static function trimPath($replace, $path, $prefix = '') {
		return trim(str_replace($replace, '', $path), '/') . $prefix;
	}

	#####################################
	######## filesystem functions #######
	#####################################

	/**
	 * reads the extension of a given filename
	 *
	 * @param string $file filename
	 * @return string extension of a given filename
	 */
	public static function getFileExtension($file) {
		return substr($file, strrpos($file, '.') + 1);
	}

	/**
	 * replaces the file extension in a given filename
	 *
	 * @param string $type new file extension
	 * @param string $file filename
	 * @return string new filename
	 */
	public static function setFileExtension($type, $file) {
		return substr($file, 0, strrpos($file, '.') + 1) . $type;
	}

	/**
	 * checks write permission of a given file (checks directory permission if file doesnt exists)
	 *
	 * @param string $file file path
	 * @return boolean true or false
	 */
	public static function checkWritePerms($file) {
		if (!is_file($file)) {
			$file = dirname($file);
		}

		if (!is_writable($file)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * deletes given files
	 *
	 * @throws Exception raised, if some files cant be deleted (throwed after deletion of all)
	 * @param array $files files
	 * @return void
	 */
	public static function deleteFiles($files) {
		// delete all old files
		$error = array();
		foreach ($files as $file) {
			if (is_file($file)) {
				if (!unlink($file)) {
					$error[] = $file;
				}
			}
		}

		if (count($error)) {
			throw new Exception('following files cant be deleted: "' . implode(', ', $error) . '"');
		}
	}

	/**
	 * creates a full path (all nonexistent directories will be created)
	 *
	 * @throws Exception raised if some path token cant be created
	 * @param string $path full path
	 * @param string $protectArea protected path (i.e. /var/www -- needed for basedir restrictions)
	 * @return void
	 */
	public static function createDir($path, $protectArea) {
		if (!is_dir($path)) {
			$path = explode('/', sgLib::trimPath($protectArea, $path));
			$tmp = '';
			foreach ($path as $dir) {
				$tmp .= $dir . '/';
				if (is_dir($protectArea . $tmp)) {
					continue;
				}

				if (!mkdir($protectArea . $tmp)) {
					throw new Exception('path "' . $protectArea . $tmp . '" cant be deleted');
				}
			}
		}
	}

	/**
	 * deletes a directory (all subdirectories and files will be deleted)
	 *
	 * @throws Exception raised if a file or directory cant be deleted
	 * @param string $path full path
	 * @return void
	 */
	public static function deleteDir($path) {
		if (!$dh = @opendir($path)) {
			throw new Exception('directory "' . $path . '" cant be readed');
		}

		while ($file = readdir($dh)) {
			$myFile = $path . '/' . $file;

			// ignore links and point directories
			if (preg_match('/\.{1,2}/', $file) || is_link($myFile)) {
				continue;
			}

			if (is_file($myFile)) {
				if (!unlink($myFile)) {
					throw new Exception('file "' . $myFile . '" cant be deleted');
				}
			} elseif (is_dir($myFile)) {
				sgLib::deleteDir($myFile);
			}
		}
		closedir($dh);

		if (!@rmdir($path)) {
			throw new Exception('directory "' . $path . '" cant be deleted');
		}
	}

	/**
	 * searches defined files in a given path recursivly
	 *
	 * @throws Exception raised if the search directory cant be read
	 * @param string $path search in this path
	 * @param string $searchRegex optional: regular expression for files
	 * @param integer $pathDepth optional: current path depth level (max 9)
	 * @return array
	 */
	public static function searchFiles($path, $searchRegex = '', $pathDepth = 0) {
		// endless recursion protection
		$fileArray = array();
		if ($pathDepth >= 9) {
			return $fileArray;
		}

		// open directory
		if (!$fhd = @opendir($path)) {
			throw new Exception('directory "' . $path . '" cant be read');
		}

		// iterate thru the directory entries
		while ($file = readdir($fhd)) {
			$filePath = $path . '/' . $file;

			// ignore links and special directories (. and ..)
			if (preg_match('/^\.{1,2}$/', $file) || is_link($filePath)) {
				continue;
			}

			// if it's a file and not excluded by the search filter, we can add it
			// to the file array
			if (is_file($filePath)) {
				if ($searchRegex == '') {
					$fileArray[] = $filePath;
				} elseif (preg_match($searchRegex, $file)) {
					$fileArray[] = $filePath;
				}

				continue;
			}

			// next dir
			if (is_dir($filePath)) {
				$fileArray = array_merge(
					$fileArray,
					(array) sgLib::searchFiles($filePath, $searchRegex, $pathDepth + 1)
				);
			}
		}
		closedir($fhd);

		return $fileArray;
	}

	/**
	 * Returns all available system languages defined in TYPO3
	 *
	 * @return array
	 */
	public static function getSystemLanguages() {
		if (class_exists('t3lib_l10n_Locales')) {
			/** @var $locales t3lib_l10n_Locales */
			$locales = t3lib_div::makeInstance('t3lib_l10n_Locales');
			$availableLanguages = $locales->getLocales();
		} else {
			$availableLanguages = explode('|', TYPO3_languages);
		}

		return $availableLanguages;
	}
}

?>
