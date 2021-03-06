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
 * contains output methods of module 'lfeditor'
 */
class tx_lfeditor_mod1_template {
	/**
	 * generates a div element with different buttons inside
	 *
	 * Structure of $buttons:
	 * $buttons[<someName>]['css'] = name of css class
	 * $buttons[<someName>]['value'] = value of button
	 * $buttons[<someName>]['onClick'] = js ability
	 * $buttons[<someName>]['type'] = submit or reset
	 *
	 * @param array $buttons button information (see above)
	 * @return string buttons (HTML code)
	 */
	public static function outputAddButtons($buttons) {
		$content = '<div class="tx-lfeditor-buttons">';
		foreach ($buttons as $button) {
			$onClick = '';
			if (!empty($button['onClick'])) {
				$onClick = 'onclick="' . $button['onClick'] . '"';
			}

			$content .= '<span class="' . $button['css'] . '">' .
				'<button ' . $onClick . ' type="' . $button['type'] . '">' .
				$button['value'] . '</button> </span>';
		}
		$content .= '</div>';

		return $content;
	}

	/**
	 * generates output for deletion of constants
	 *
	 * @param string $constant constant name
	 * @return string output (HTML code)
	 */
	public static function outputDeleteConst($constant) {
		// generate hidden field
		$content = '<input type="hidden" name="submit" value="1" />';

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// del-question
		$content .= $lang->getLL('function.const.delete.question');
		$content .= '<p class="tx-lfeditor-delConst">' . $constant . '</p>';

		// checkbox
		$content .= '<p><input id="delAllLang" type="checkbox" name="delAllLang" ' .
			'value="1" checked="checked">';
		$content .= '<label for="delAllLang"> ' .
			$lang->getLL('function.const.delete.inAllLanguages') . '</label> </p>';

		// submit and reset
		$buttons = array(
			'submit' => array(
				'css' => 'tx-lfeditor-buttonSubmit',
				'value' => $lang->getLL('button.delete'),
				'type' => 'submit'
			),
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		return $content;
	}

	/**
	 * generates output for renaming of constants
	 *
	 * @param string $constant constant name
	 * @return string output (HTML code)
	 */
	public static function outputRenameConst($constant) {
		// generate hidden field
		$content = '<input type="hidden" name="submit" value="1" />';

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// del-question
		$content .= '<p>' . $lang->getLL('function.const.rename.hint') . '</p> <br />';
		$content .= '<p><input type="text" name="newConst" value="' . $constant . '" /> ';
		$content .= '<strong>' . $lang->getLL('function.const.rename.newName') .
			'</strong></p>';

		// submit and reset
		$buttons = array(
			'submit' => array(
				'css' => 'tx-lfeditor-buttonSubmit',
				'value' => $lang->getLL('button.rename'),
				'type' => 'submit'
			),
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		return $content;
	}

	/**
	 * generates output for searching of constants
	 *
	 * @param string $searchStr value of text field
	 * @param array $resultArray result array
	 * @param string $preMsg pre message string
	 * @param boolean $checked set to true if you want case-sensitive enabled by default
	 * @return string output (HTML code)
	 */
	public static function outputSearchConst($searchStr, $resultArray, $preMsg, $checked) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// input dialog
		$checked = $checked ? 'checked="checked"' : '';
		$content = '<p id="tx-lfeditor-caseSensitiveBox"style="margin-bottom: 5px;">' .
			'<input type="checkbox" name="caseSensitive" value="1" ' .
			'onchange="lfe_processFormData(xajax.getFormValues(\'mainForm\'))" ' .
			'name="caseSensitive" value="1" ' . $checked . '/> ' .
			$lang->getLL('function.search.caseSensitiveCheckbox') . '</p>';
		$content .= '<p><input type="text" name="searchStr" size="30" value="' . $searchStr . '" /> ';
		$content .= '<span class="tx-lfeditor-submit">' . '<button type="submit">' .
			$lang->getLL('button.search') . '</button></span></p>';

		// display result array
		$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5">';
		$content .= '<legend class="bgColor3">' .
			$lang->getLL('function.const.search.result') . '</legend>';

		// static message
		if (!empty($preMsg)) {
			$content .= '<fieldset class="bgColor4">' . $preMsg . '</fieldset>';
		}

		// Generate the module token for TYPO3 6.2
		$token = self::generateModuleToken();

		// generate result content
		$content .= '<input type="hidden" name="constant" value="" />';
		foreach ($resultArray as $langKey => $data) {
			$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' .
				$langKey . '</legend> <dl>';
			foreach ($data as $label => $value) {

				$content .= '<dt>';
				$content .= '<a href="#" title="' . $label . '" ' .
					'onclick="submitRedirectForm(\'constant\', \'' . $label . '\',\'' . $token . '\');">' .
					$label . '</a></dt>';
				$content .= '<dd>' . htmlspecialchars($value) . '</dd>';
			}
			$content .= '</dl> </fieldset>';
		}
		$content .= '</fieldset>';

		// additional hidden form values
		$content .= '<input type="hidden" name="submitted" value="1" />';

		return $content;
	}

	/**
	 * generates form with token input field
	 *
	 * @param string $curToken current token
	 * @return string input field (HTML code)
	 */
	public static function fieldSetToken($curToken) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$content = '<input type="text" size="1" maxlength="1" name="usedToken" ' .
			'value="' . $curToken . '" /> ';
		$content .= '<span class="tx-lfeditor-submit"> <button type="submit">' .
			$lang->getLL('select.explodeToken') . '</button> </span>';

		return $content;
	}

	/**
	 * walks recursive through a tree array and generates the html code
	 *
	 * @param array $tree
	 * @param string $content content buffer (reference)
	 * @param array $myIDs id buffer to get all ids of each hidden element (reference)
	 * @param boolean $treeHide should the tree entries hidden or visible at startup
	 * @param array|string $parentBranch previous branches of a dimension
	 * @param array $numBranches last element of each dimension
	 * @param array|int $last current rec level (dont touch)
	 * @param integer $curDim maximum rec level (default 9999)
	 * @param int $maxDim
	 * @return boolean always true
	 */
	public static function genTree(
		$tree, &$content, &$myIDs, $treeHide, $parentBranch = '',
		$numBranches = array(), $last = array(), $curDim = 0, $maxDim = 9999
	) {
		// prevent endless loops
		if ($curDim >= $maxDim) {
			return TRUE;
		}

		// initial definition list
		if (!$curDim) {
			$content .= '<dl class="tx-lfeditor-treeview">';
		}

		// Generate the module token for TYPO3 6.2
		$token = self::generateModuleToken();

		// recursive loop
		$branches = array_keys($tree[$curDim]);
		$numBranches[$curDim] = count($tree[$curDim]);
		for ($curBranch = 0, $numDisplayed = 0; $curBranch < $numBranches[$curDim]; ++$curBranch) {
			if ($tree[$curDim][$branches[$curBranch]]['parent'] != $parentBranch) {
				continue;
			}

			// get lines and blanks (visual issues)
			unset($cont);
			$cont = '';
			for ($tmp = 0, $lineSpace = 0; $tmp < $curDim; ++$tmp) {
				if ($numBranches[$tmp] > 1 && !$last[$tmp]) {
					$cont .= '<img src="' . t3lib_extMgm::extRelPath('lfeditor') . 'res/images/line.gif" alt="line" ' .
						'style="margin-left: ' . $lineSpace . 'px;" />';
					$lineSpace = 0;
				} else {
					$lineSpace += 18;
				}
			}

			// bottom element
			$bottom = 0;
			$last[$curDim] = 0;
			$picAdd = '';
			if (++$numDisplayed >=
				$tree[$curDim - 1][$tree[$curDim][$branches[$curBranch]]['parent']]['childs']
			) {
				$last[$curDim] = 1;
				$bottom = 1;
				$picAdd = 'Bottom';
			}

			// children?
			$name = $tree[$curDim][$branches[$curBranch]]['name'];
			if ($tree[$curDim][$branches[$curBranch]]['type'] == 1) {
				$name = '<span class="tx-lfeditor-badMarkup">' . $name . '</span>';
			} elseif ($tree[$curDim][$branches[$curBranch]]['type'] == 2) {
				$name = '<span class="tx-lfeditor-specialMarkup">' . $name . '</span>';
			}

			if ($tree[$curDim][$branches[$curBranch]]['childs']) {
				// generate id
				$myID = $tree[$curDim][$branches[$curBranch]]['name'] . $curDim . $curBranch;
				$myIDs[$myID] = $bottom;

				// add tree branch
				$pic = 'Minus';
				$style = '';
				if ($treeHide && $curDim) {
					$pic = 'Plus';
					$style = 'style="display: none;"';
				}

				// generate branch
				$cont .= '<a href="javascript:openCloseTreeEntry(\'' . t3lib_extMgm::extRelPath(
						'lfeditor'
					) . '\', \'' . $myID . '\',' .
					'\'pic' . $myID . '\',' . $bottom . ')">';
				$cont .= '<img id="pic' . $myID . '" src="' . t3lib_extMgm::extRelPath('lfeditor') . 'res/images/tree' .
					$pic . $picAdd . '.gif" ' . 'alt="tree' . $pic . $picAdd .
					'" style="margin-left: ' . $lineSpace . 'px;margin-right: 5px;" />' . $name;
				$cont .= '</a>';

				$content .= '<dt>' . $cont . '</dt>';
				$content .= '<dd><dl class="tx-lfeditor-treeview" ' . $style . ' id="' . $myID . '">';

				tx_lfeditor_mod1_template::genTree(
					$tree, $content, $myIDs, TRUE,
					$branches[$curBranch], $numBranches, $last, $curDim + 1
				);
				$content .= '</dl></dd>';
			} else {
				if ($name == $tree[$curDim][$branches[$curBranch]]['name']) {
					$name = '<span class="tx-lfeditor-goodMarkup">' . $name . '</span>';
				}

				$cont .= '<img src="' . t3lib_extMgm::extRelPath(
						'lfeditor'
					) . 'res/images/join' . $picAdd . '.gif" alt="join' . $picAdd . '" ' .
					'style="margin-left: ' . $lineSpace . 'px; margin-right: 5px;" /> ';
				$cont .= '<a href="#" title="' . $branches[$curBranch] . '" ' .
					'onclick="submitRedirectForm(\'constant\', \'' . $branches[$curBranch] . '\',
					\'' . $token . '\');"> ' . $name . '</a>';
				$content .= '<dd>' . $cont . '</dd>';
			}
		}

		// initial definition list
		if (!$curDim) {
			$content .= '</dl>';
		}

		return TRUE;
	}

	/**
	 * generates output for tree view of constants
	 *
	 * @param array $tree tree (see above)
	 * @param boolean $treeHide should the tree entries hidden or visible at startup (default is true)
	 * @return string output (HTML code)
	 */
	public static function outputTreeView($tree, $treeHide = TRUE) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// display result array
		$content = '<fieldset class="tx-lfeditor-fieldset bgColor5">';
		$content .= '<legend class="bgColor3">' .
			$lang->getLL('function.const.treeview.treeview') . '</legend>';

		// hint
		$content .= '<p class="tx-lfeditor-goodMarkup"> xyz -- ' .
			$lang->getLL('function.const.treeview.goodMarkupHint') . '</p>';
		$content .= '<p class="tx-lfeditor-badMarkup"> xyz -- ' .
			$lang->getLL('function.const.treeview.badMarkupHint') . '</p>';
		$content .= '<p class="tx-lfeditor-specialMarkup"> xyz -- ' .
			$lang->getLL('function.const.treeview.specialMarkupHint') . '</p>';

		// generate tree
		$treeContent = '';
		$myIDs = array();
		tx_lfeditor_mod1_template::genTree($tree, $treeContent, $myIDs, $treeHide);

		// get unhide/hide all feature
		$JSArgs = array();
		foreach ($myIDs as $myID => $bottom) {
			$JSArgs[] = '\'' . $myID . '\',\'pic' . $myID . '\',' . $bottom;
		}
		$JSArgs = implode(',', $JSArgs);

		// generate output
		$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' .
			'<a href="javascript:openCloseTreeEntry(\'' . t3lib_extMgm::extRelPath(
				'lfeditor'
			) . '\', ' . $JSArgs . ');">' .
			$lang->getLL('function.const.treeview.hideUnhideAll') . '</a>' .
			'</legend>' . $treeContent . '</fieldset>';
		$content .= '</fieldset>';

		$content .= '<input type="hidden" name="constant" value="" />';
		$content .= '<input type="hidden" name="submitted" value="1" />';

		return $content;
	}

	/**
	 * generates output for editing of constants
	 *
	 * @param array $langArray language shortcuts
	 * @param string $constant constant name
	 * @param array $localLang language content array
	 * @param integer $textAreaRows amount of rows in textarea
	 * @return string output (HTML code)
	 */
	public static function outputEditConst($langArray, $constant, $localLang, $textAreaRows) {
		// additional hidden form values
		$content = '<input type="hidden" name="submit" value="1" />';

		/** @var \TYPO3\CMS\Lang\LanguageService $langInstance */
		$langInstance = $GLOBALS['LANG'];

		// generate form
		$k = 0;
		foreach ($langArray as $lang) {
			$cssClass = 'tx-lfeditor-fleft';
			if ($k++ % 2) {
				$cssClass = 'tx-lfeditor-fright';
			}

			// add textentry
			$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 ' . $cssClass . '">';
			$content .= '<legend class="bgColor3">' . $lang . '</legend>';

			$textareaLanguage = ($lang === 'default' ? 'en' : $lang);
			$content .= '<textarea class="tx-lfeditor-textarea" ' .
				'rows="' . $textAreaRows . '" cols="80" ' .
				'name="newLang[' . $lang . '][' . $constant . ']" lang="' . $textareaLanguage . '" x:lang="' . $textareaLanguage . '">';

			if ($localLang[$lang]) {
				$content .= preg_replace('/<br.*>/U', "\n", $localLang[$lang][$constant]);
			}
			$content .= '</textarea> </fieldset>';
		}

		// button definition
		$buttons = array(
			'submit' => array(
				'css' => 'tx-lfeditor-buttonSubmit',
				'value' => $langInstance->getLL('button.save'),
				'type' => 'submit'
			),
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		return $content;
	}

	/**
	 * generates output for adding of constants
	 *
	 * @param array $langArray language shortcuts
	 * @param string $constant constant name
	 * @param array $defValues default values
	 * @param integer $textAreaRows amount of rows in textarea
	 * @return string output (HTML code)
	 */
	public static function outputAddConst($langArray, $constant, $defValues, $textAreaRows) {
		/** @var \TYPO3\CMS\Lang\LanguageService $langInstance */
		$langInstance = $GLOBALS['LANG'];

		// constant name field
		$content = '<p><input type="text" name="nameOfConst" value="' . $constant . '" /> ';
		$content .= '<strong>' . $langInstance->getLL('function.const.add.name') . '</strong></p>';

		// additional hidden form values
		$content .= '<input type="hidden" name="submit" value="1" />';

		// generate form
		$k = 0;
		foreach ($langArray as $lang) {
			$cssClass = 'tx-lfeditor-fleft';
			if ($k++ % 2) {
				$cssClass = 'tx-lfeditor-fright';
			}

			// add text entry
			$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 ' . $cssClass . '">';
			$content .= '<legend class="bgColor3">' . $lang . '</legend>';

			$textareaLanguage = ($lang === 'default' ? 'en' : $lang);
			$content .= '<textarea class="tx-lfeditor-textarea" ' .
				'rows="' . $textAreaRows . '" cols="80" id="' . $lang . '" ' .
				'name="newLang[' . $lang . ']" lang="' . $textareaLanguage . '" x:lang="' . $textareaLanguage . '">' .
				$defValues[$lang] . '</textarea> </fieldset>';
		}

		// button definition
		$buttons = array(
			'submit' => array(
				'css' => 'tx-lfeditor-buttonSubmit',
				'value' => $langInstance->getLL('button.save'),
				'type' => 'submit'
			),
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		return $content;
	}

	/**
	 * generates output for editing of language files
	 *
	 * Structure of $constValues:
	 * $constValues[<constant>]['edit'] = <value>
	 * $constValues[<constant>]['pattern'] = <value>
	 * $constValues[<constant>]['default'] = <value>
	 *
	 * @param array $constValues needed constant values (see above)
	 * @param integer $curConsts current number of constants in this session
	 * @param integer $siteConsts number of constants for this page
	 * @param integer $totalConsts number of total amount of constants
	 * @param string $langName edit language name
	 * @param string $patternName pattern language name
	 * @param boolean $parallelEdit set to true if you want the parallel editing mode
	 * @param boolean $buttonBack set to true if you want a back button
	 * @param boolean $buttonNext set to true if you want a next button
	 * @param integer $textAreaRows amount of rows in textarea
	 * @return string output (HTML code)
	 */
	public static function outputEditLangfile(
		$constValues, $curConsts, $siteConsts, $totalConsts,
		$langName, $patternName, $parallelEdit, $buttonBack, $buttonNext, $textAreaRows
	) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// information
		$content = '<p><span id="tx-lfeditor-numberCur">' . $curConsts . '</span> / ' .
			'<span id="tx-lfeditor-numberOf">' . $totalConsts . '</span></p>';

		// additional hidden form values
		$content .= '<input type="hidden" name="session" value="0" />';
		$content .= '<input type="hidden" name="buttonType" value="0" />';
		$content .= '<input type="hidden" name="numSessionConsts" value="' . $curConsts . '" />';
		$content .= '<input type="hidden" name="numLastPageConsts" value="' . $siteConsts . '" />';
		$content .= '<input type="hidden" name="submitted" value="1" />';

		// loop constants
		$k = 0;
		foreach ($constValues as $constant => $values) {
			$cssClass = 'tx-lfeditor-fleft';
			if (($k++ % 2) && !$parallelEdit) {
				$cssClass = 'tx-lfeditor-fright';
			}

			// generate legend
			if (strlen($constant) >= 40) {
				$lconstant = substr($constant, 0, 40) . '...';
			} else {
				$lconstant = $constant;
			}

			// add edit fieldset
			$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 ' . $cssClass . '">';
			$content .= '<legend class="bgColor3">' . $langName . ': ' . $lconstant . '</legend>';

			// add textarea with default value
			$textareaLanguage = ($langName === 'default' ? 'en' : $langName);
			$content .= '<textarea class="tx-lfeditor-textarea" ' .
				'rows="' . $textAreaRows . '" cols="80" ' .
				'name="newLang[' . $langName . '][' . $constant . ']" lang="' . $textareaLanguage . '" x:lang="' . $textareaLanguage . '">' .
				preg_replace('/<br.*>/U', "\n", $values['edit']) . '</textarea>';

			// add default value
			if (!empty($values['default'])) {
				$content .= '<p class="tx-lfeditor-defaultTranslation bgColor3">' .
					htmlspecialchars($values['default']) . ' </p>';
			}
			$content .= '</fieldset>';

			// add pattern fieldset
			if ($parallelEdit) {
				$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 tx-lfeditor-fright">';
				$content .= '<legend class="bgColor3">' . $patternName .
					': ' . $lconstant . '</legend>';

				// add textarea with default value
				$textareaLanguage = ($langName === 'default' ? 'en' : $langName);
				$content .= '<textarea class="tx-lfeditor-textarea" ' .
					'rows="' . $textAreaRows . '" cols="80" ' .
					'name="newLang[' . $patternName . '][' . $constant . ']" lang="' . $textareaLanguage . '" x:lang="' . $textareaLanguage . '">' .
					preg_replace('/<br.*>/U', "\n", $values['pattern']) . '</textarea>';

				// add default value
				if (!empty($values['default'])) {
					$content .= '<p class="tx-lfeditor-defaultTranslation bgColor3">' .
						htmlspecialchars($values['default']) . ' </p>';
				}

				$content .= '</fieldset>';
			}
		}

		// button definitions
		if ($buttonBack) {
			$buttons['back'] = array(
				'css' => 'tx-lfeditor-buttonSessionBack',
				'value' => $lang->getLL('button.session.back'),
				'onClick' => 'submitSessionLangFileEdit(1);',
				'type' => 'submit',
			);
		}
		if ($buttonNext) {
			$buttons['next'] = array(
				'css' => 'tx-lfeditor-buttonSessionNext',
				'value' => $lang->getLL('button.session.next'),
				'onClick' => 'submitSessionLangFileEdit(2);',
				'type' => 'submit',
			);
		}
		$buttons['submit'] = array(
			'css' => 'tx-lfeditor-buttonSubmit',
			'value' => $lang->getLL('button.save'),
			'onClick' => 'submitSessionLangFileEdit(3, 0);',
			'type' => 'submit',
		);
		$buttons['cancel'] = array(
			'css' => 'tx-lfeditor-buttonCancel',
			'value' => $lang->getLL('button.cancel'),
			'onClick' => 'submitSessionLangFileEdit(-1);',
			'type' => 'submit',
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		return $content;
	}

	/**
	 * generates output for email form
	 *
	 * @param array $metaArray meta information
	 * @param integer $textAreaRows amount of rows in textarea
	 * @return string output (HTML code)
	 */
	public static function outputGeneralEmail($metaArray, $textAreaRows) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$header = $lang->getLL('function.general.mail.form');
		$content = '<fieldset class="tx-lfeditor-fieldset bgColor5 tx-lfeditor-fleft">';
		$content .= '<legend class="bgColor3">' . $header . '</legend>';

		// to email address
		$label = $lang->getLL('function.general.mail.toAddress');
		$content .= '<label for="toEmail" class="tx-lfeditor-label">' . $label . '</label>';
		$content .= '<input type="text" id="toEmail"  class="tx-lfeditor-input"' .
			'name="mailItEmailToAddress" value="' . $metaArray['authorEmail'] . '" />';

		// from email address
		$label = $lang->getLL('function.general.mail.fromAddress');
		$content .= '<label for="fromEmail" class="tx-lfeditor-label">' . $label . '</label>';
		$content .= '<input type="text" id="fromEmail"  class="tx-lfeditor-input"' .
			'name="mailItEmailFromAddress" />';

		// email subject
		$label = $lang->getLL('function.general.mail.subject');
		$content .= '<label for="subject" class="tx-lfeditor-label">' . $label . '</label>';
		$content .= '<input type="text" id="subject"  class="tx-lfeditor-input"' .
			'name="mailItEmailSubject" />';

		// email text
		$content .= '<textarea class="tx-lfeditor-textarea" ' . '
			rows="' . $textAreaRows . '" cols="80" ' .
			'name="mailItEmailText">Dear ' . $metaArray['authorName'] . '</textarea>';

		$content .= '</fieldset>';

		// hidden fields
		$content .= '<input type="hidden" name="sendMail" value="1" />';

		return $content;
	}

	/**
	 * generates output for general informations
	 *
	 * Structure of $infos:
	 * $infos[$langKey]['origin'] == (array) language origins (relTypo3File)
	 * $infos[$langKey]['meta'] == (array) meta information (only default needed)
	 * $infos[$langKey]['type'] == (string) location type (translated string)
	 * $infos[$langKey]['type2'] == (string) language type (merged|l10n|splitted)
	 * $infos[$langKey]['numTranslated'] == (integer) translated constants
	 * $infos[$langKey]['numUntranslated'] == (integer) untranslated constants
	 * $infos[$langKey]['numUnknown'] == (integer) unknown constants
	 * $infos[$langKey]['email'] == (boolean) mailIt pre selection
	 *
	 * @param array $infos see above
	 * @param string $refLang reference language
	 * @param integer $textAreaRows amount of rows in textarea
	 * @param boolean $flagSpecial set to true if you want some special options (splitting dialog, meta edit)
	 * @return string output (HTML code)
	 */
	public static function outputGeneral($infos, $refLang, $textAreaRows, $flagSpecial = FALSE) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		$summary = $lang->getLL('table.fileInfo');
		$content = '<table id="tx-lfeditor-table" summary="' . $summary . '">';

		// table header
		$content .= '<thead><tr>';
		$content .= '<th class="bgColor5">' . $lang->getLL('lang.shortcut') . '</th>';
		$content .= '<th class="bgColor5">' . $lang->getLL('lang.state') . '</th>';
		$content .= '<th class="bgColor5">' . $lang->getLL('ext.type') . '</th>';
		$content .= '<th class="bgColor5">' . $lang->getLL('lang.origin') . '</th>';
		if ($flagSpecial) {
			$content .= '<th id="tx-lfeditor-table-markup4"><img src="' . t3lib_extMgm::extRelPath(
					'lfeditor'
				) . 'res/images/mail.gif" alt="' .
				$lang->getLL('function.backupMgr.delete') . '" /></th>';
			$char = substr($lang->getLL('function.general.split.splitNormal'), 0, 1);
			$content .= '<th id="tx-lfeditor-table-markup1">' . strtoupper($char) . '</th>';
			$char = substr($lang->getLL('function.general.split.splitL10n'), 0, 1);
			$content .= '<th id="tx-lfeditor-table-markup2">' . strtoupper($char) . '</th>';
			$char = substr($lang->getLL('function.general.split.merge'), 0, 1);
			$content .= '<th id="tx-lfeditor-table-markup3">' . strtoupper($char) . '</th>';
		}
		$content .= '</tr></thead>';

		// Generate the module token for TYPO3 6.2
		$token = self::generateModuleToken();

		// table data
		$content .= '<tbody>';
		foreach ($infos as $langKey => $info) {
			// language shortcut
			$content .= '<tr><td class="bgColor4"><a href="#" title="' . $langKey . '" ' .
				'onclick="submitRedirectForm(\'language\',\'' . $langKey . '\', \'' . $token . '\');">' .
				$langKey . '</a></td>';

			// state and constant information
			$constInfo = '(<span class="tx-lfeditor-goodMarkup">' .
				$info['numTranslated'] . '</span>-<span class="tx-lfeditor-specialMarkup">' .
				$info['numUnknown'] . '</span>-<span class="tx-lfeditor-badMarkup">' .
				$info['numUntranslated'] . '</span>)';

			if ($info['numTranslated'] >= $infos[$refLang]['numTranslated']) {
				$content .= '<td class="bgColor4"><span class="tx-lfeditor-goodMarkup">' .
					$lang->getLL('lang.complete') . '</span><br />' . $constInfo . '</td>';
			} else {
				$content .= '<td class="bgColor4"><span class="tx-lfeditor-badMarkup">' .
					$lang->getLL('lang.incomplete') . '</span><br />' . $constInfo . '</td>';
			}

			// type and origin
			$content .= '<td class="bgColor4">' . $info['type'] . '</td>';
			$content .= '<td class="bgColor4">' . $info['origin'] . '</td>';

			// zip mail, merge, normal split and l10n split options
			if ($flagSpecial) {
				// pre selection
				$checked = '';
				if ($info['email']) {
					$checked = 'checked="checked"';
				}

				$set = '-';
				if ($langKey == 'default' || $info['type2'] != 'merged') {
					$set = '<input type="checkbox" ' . $checked .
						' name="mailIt[' . $langKey . ']" value="1" />';
				}
				$content .= '<td class="bgColor4">' . $set . '</td>';

				$set = '-';
				if ($langKey != 'default' && $info['type2'] != 'splitted') {
					$set = '<input type="radio" name="langModes[' . $langKey . ']" value="1" />';
				}
				$content .= '<td class="bgColor4">' . $set . '</td>';

				$set = '-';
				if ($langKey != 'default' && $info['type2'] != 'l10n') {
					$set = '<input type="radio" name="langModes[' . $langKey . ']" value="2" />';
				}
				$content .= '<td class="bgColor4">' . $set . '</td>';

				$set = '-';
				if ($langKey != 'default' && $info['type2'] != 'merged') {
					$set = '<input type="radio" name="langModes[' . $langKey . ']" value="3" />';
				}
				$content .= '<td class="bgColor4">' . $set . '</td>';
			}
			$content .= '</tr>';
		}
		$content .= '</tbody></table>';

		// generate meta handling fieldset dialog
		$header = $lang->getLL('function.general.metaInfo.metaInfo');
		$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 tx-lfeditor-fleft">';
		$content .= '<legend class="bgColor3">' . $header . '</legend>';

		// type and csh table
		$header = $lang->getLL('function.general.metaInfo.type');
		$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' . $header . '</legend>';
		$content .= '<div><select id="metaType" name="meta[type]" onchange="metaTypeCheck();">';

		$options = array('', 'module', 'database', 'CSH');
		foreach ($options as $option) {
			$selected = '';
			if ($option == $infos['default']['meta']['type']) {
				$selected = 'selected="selected"';
			}

			$content .= '<option value="' . $option . '" ' . $selected . '>' .
				(empty($option) ? '--' : $option) . '</option>';
		}
		$content .= '</select>';

		$content .= $lang->getLL('function.general.metaInfo.cshTable') . '&nbsp;';
		$disabled = $infos['default']['meta']['type'] == 'CSH' ? '' : 'disabled="disabled"';
		$content .= '<input type="text" class="tx-lfeditor-input" ' . $disabled . ' id="metaCSHTable" name="meta[csh_table]"' .
			'value="' . $infos['default']['meta']['csh_table'] . '" /></div>';
		$content .= '</fieldset>';

		// author
		$header = $lang->getLL('function.general.metaInfo.author');
		$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' . $header . '</legend>';
		$label = $lang->getLL('function.general.metaInfo.author.name');
		$value = $infos['default']['meta']['authorName'];
		$content .= '<div><label for="name" class="tx-lfeditor-label">' . $label . '</label>';
		$content .= '<input type="text" id="name" class="tx-lfeditor-input" ' .
			'name="meta[authorName]" value="' . $value . '" /></div>';

		$label = $lang->getLL('function.general.metaInfo.author.email');
		$value = $infos['default']['meta']['authorEmail'];
		$content .= '<div><label for="email" class="tx-lfeditor-label">' . $label . '</label>';
		$content .= '<input type="text" id="email" class="tx-lfeditor-input" ' .
			'name="meta[authorEmail]" value="' . $value . '" /></div>';
		$content .= '</fieldset>';

		// description
		$header = $lang->getLL('function.general.metaInfo.desc');
		$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' . $header . '</legend>';
		$desc = $infos['default']['meta']['description'];
		$content .= '<textarea class="tx-lfeditor-textarea" ' .
			'rows="' . $textAreaRows . '" cols="80" ' .
			'name="meta[description]">' . preg_replace('/<br.*>/U', "\n", $desc) . '</textarea>';
		$content .= '</fieldset> </fieldset>';

		// options dialog
		$header = $lang->getLL('function.general.options');
		$content .= '<fieldset class="tx-lfeditor-fieldset bgColor5 tx-lfeditor-fright">';
		$content .= '<legend class="bgColor3">' . $header . '</legend>';

		// split/merge options
		if ($flagSpecial) {
			$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' .
				$lang->getLL('function.general.split.split') . '</legend>';
			$value = $lang->getLL('function.general.split.splitNormal');
			$content .= '<p> <input type="radio" name="splitFile" id="splitNormal" value="1" /> ' .
				'<label for="splitNormal">' . $value . '</label></p>';
			$value = $lang->getLL('function.general.split.splitL10n');
			$content .= '<p> <input type="radio" name="splitFile" id="splitL10n" value="2" /> ' .
				'<label for="splitL10n">' . $value . '</label></p>';
			$value = $lang->getLL('function.general.split.merge');
			$content .= '<p> <input type="radio" name="splitFile" id="merge" value="3" /> ' .
				'<label for="merge">' . $value . '</label></p>';
			$content .= '</fieldset>';
		}

		// transform options
		$header = $lang->getLL('function.general.transform.transform');
		$content .= '<fieldset class="bgColor4"><legend class="bgColor3">' . $header . '</legend>';
		$content .= '<p> <input type="radio" name="transFile" id="xlf" value="xlf" /> ' .
			'<label for="xlf">XLF</label></p>';
		$value = $lang->getLL('function.general.transform.xml');
		$content .= '<p> <input type="radio" name="transFile" id="xml" value="xml" /> ' .
			'<label for="xml">' . $value . '</label></p>';
		$value = $lang->getLL('function.general.transform.php');
		$content .= '<p> <input type="radio" name="transFile" id="php" value="php" /> ' .
			'<label for="php">' . $value . '</label></p>';
		$content .= '</fieldset> </fieldset>';

		// submit and reset
		$buttons = array(
			'submit' => array(
				'css' => 'tx-lfeditor-buttonSubmit',
				'value' => $lang->getLL('button.save'),
				'type' => 'submit'
			),
			'reset' => array(
				'css' => 'tx-lfeditor-buttonReset',
				'value' => $lang->getLL('button.reset'),
				'type' => 'reset'
			),
		);
		$content .= tx_lfeditor_mod1_template::outputAddButtons($buttons);

		// hidden fields
		$content .= '<input type="hidden" name="submitted" value="1" />';
		$content .= '<input type="hidden" name="language" value="" />';

		return $content;
	}

	/**
	 * generates output of a language diff
	 *
	 * @param array $diff language content (difference between backup and origin)
	 * @param array $metaDiff meta content (difference between backup and origin)
	 * @param array $origLang original language content
	 * @param array $backupLang backup language content
	 * @param array $origOriginLang original origins of each language
	 * @param array $backupOriginLang backup origins of each language
	 * @param array $origMeta original meta content
	 * @param array $backupMeta backup meta content
	 * @return string output (html code)
	 */
	public static function outputManageBackupsDiff(
		$diff, $metaDiff, $origLang,
		$backupLang, $origOriginLang, $backupOriginLang, $origMeta, $backupMeta
	) {
		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// begin fieldset
		$content = '<fieldset class="tx-lfeditor-fieldset bgColor5">';
		$content .= '<legend class="bgColor3">' .
			$lang->getLL('function.backupMgr.diff.diff') . '</legend>';

		// hint
		$content .= '<p class="tx-lfeditor-goodMarkup"> xyz -- ' .
			$lang->getLL('function.backupMgr.diff.goodMarkupHint') . '</p>';
		$content .= '<p class="tx-lfeditor-badMarkup"> xyz -- ' .
			$lang->getLL('function.backupMgr.diff.badMarkupHint') . '</p>';

		// meta entry
		if (count($metaDiff)) {
			$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' .
				$lang->getLL('function.backupMgr.diff.meta') . '</legend> <dl>';
			foreach ($metaDiff as $label => $value) {
				$value = htmlspecialchars($value);

				if (!isset($backupMeta[$label])) // constant added
				{
					$content .= '<dt class="tx-lfeditor-goodMarkup">' . $label . '</dt>';
				} elseif (!isset($origMeta[$label])) // constant lost
				{
					$content .= '<dt class="tx-lfeditor-badMarkup">' . $label . '</dt>';
				} else // constant normal
				{
					$content .= '<dt>' . $label . '</dt>';
				}
				$content .= '<dd>' . $value . '</dd>';
			}
			$content .= '</dl> </fieldset>';
		}

		// loop each language entry
		foreach ($diff as $langKey => $data) {
			if (!count($data) && ($origOriginLang[$langKey] == $backupOriginLang[$langKey])) {
				continue;
			}

			// get state
			if ($backupOriginLang[$langKey] != $backupOriginLang['default']) {
				try {
					$state = $lang->getLL('lang.splitted') . ' -- ';
					$state .= typo3Lib::transTypo3File($backupOriginLang[$langKey], FALSE);
				} catch (Exception $e) {
					$state = $backupOriginLang[$langKey];
				}
			} else {
				$state = $lang->getLL('lang.merged');
			}

			$content .= '<fieldset class="bgColor4"> <legend class="bgColor3">' .
				$langKey . ' (' . $state . ')</legend> <dl>';
			foreach ($data as $label => $value) {
				$value = htmlspecialchars($value);

				if (!isset($backupLang[$langKey][$label])) // constant added
				{
					$content .= '<dt class="tx-lfeditor-goodMarkup">' . $label . '</dt>';
				} elseif (!isset($origLang[$langKey][$label])) // constant lost
				{
					$content .= '<dt class="tx-lfeditor-badMarkup">' . $label . '</dt>';
				} else // constant normal
				{
					$content .= '<dt>' . $label . '</dt>';
				}
				$content .= '<dd>' . $value . '</dd>';
			}
			$content .= '</dl> </fieldset>';
		}
		$content .= '</fieldset>';

		return $content;
	}

	/**
	 * generates output for management of backups
	 *
	 * @param array $metaArray meta information data (only the extensions part)
	 * @param string $extPath extension path (absolute)
	 * @return string output (HTML code)
	 */
	public static function outputManageBackups($metaArray, $extPath) {
		// generate form
		$content = '<input type="hidden" name="submitted" value="1" />';
		$content .= '<input type="hidden" name="delete" value="0" />';
		$content .= '<input type="hidden" name="restore" value="0" />';
		$content .= '<input type="hidden" name="origDiff" value="0" />';
		$content .= '<input type="hidden" name="deleteAll" value="0" />';
		$content .= '<input type="hidden" name="file" value="" />';
		$content .= '<input type="hidden" name="langFile" value="" />';

		/** @var \TYPO3\CMS\Lang\LanguageService $lang */
		$lang = $GLOBALS['LANG'];

		// begin table
		$content .= '<table id="tx-lfeditor-table" ' .
			'summary="' . $lang->getLL('table.backups') . '">';

		// table header
		$content .= '<thead><tr>';
		$content .= '<th class="bgColor5"> ' .
			$lang->getLL('function.backupMgr.date') . ' </th>';
		$content .= '<th class="bgColor5"> ' .
			$lang->getLL('function.backupMgr.state') . ' </th>';
		$content .= '<th class="bgColor5"> ' .
			$lang->getLL('lang.file.file') . ' </th>';
		$content .= '<th id="tx-lfeditor-table-markup1" class="bgColor5">' .
			'<a href="#" title="' . $lang->getLL('function.backupMgr.deleteAll') . '" ' .
			'onclick="submitBackupForm(\'\', \'\', 0, 0, 1, 0);">' .
			'<img src="' . t3lib_extMgm::extRelPath('lfeditor') . 'res/images/garbage.gif" alt="' .
			$lang->getLL('function.backupMgr.delete') . '" /> </a> </th>';
		$recover = strtoupper(substr($lang->getLL('function.backupMgr.recover'), 0, 1));
		$content .= '<th id="tx-lfeditor-table-markup2">' . $recover . '</th>';
		$diff = strtoupper(substr($lang->getLL('function.backupMgr.diff.diff'), 0, 1));
		$content .= '<th id="tx-lfeditor-table-markup3">' . $diff . '</th>';
		$content .= '</tr></thead>';

		// table body
		$content .= '<tbody>';
		$keys = array_keys($metaArray);
		foreach ($keys as $langFile) {
			foreach ($metaArray[$langFile] as $filename => $informations) {
				// get path to filename
				$backupPath = $informations['pathBackup'];
				$file = typo3Lib::fixFilePath(PATH_site . '/' . $backupPath . '/' . $filename);
				$origFile = typo3Lib::fixFilePath($extPath . '/' . $langFile);

				// check state
				$content .= '<tr>';
				$stateBool = FALSE;
				if (!is_file($file)) {
					$state = '<td class="tx-lfeditor-badMarkup bgColor4">' .
						$lang->getLL('function.backupMgr.missing') . '</td>';
				} elseif (!is_file($origFile)) {
					$state = '<td class="tx-lfeditor-badMarkup bgColor4">' .
						$lang->getLL('lang.file.missing') . '</td>';
				} else {
					$stateBool = TRUE;
					$state = '<td class="tx-lfeditor-goodMarkup bgColor4">' .
						$lang->getLL('function.backupMgr.ok') . '</td>';
				}

				// generate row
				$content .= '<td class="bgColor4">' .
					date('Y-m-d<b\r />H:i:s', $informations['createdAt']) . '</td>';
				$content .= $state;
				$content .= '<td class="bgColor4">' . $langFile . '</td>';

				// delete
				$name = $lang->getLL('function.const.delete.delete');
				$content .= '<td class="bgColor4"> <a href="#" title="' . $name . '" ' .
					'onclick="submitBackupForm(\'' . $filename . '\', \'' . $langFile . '\', ' .
					'1, 0, 0, 0);"> <img src="' . t3lib_extMgm::extRelPath(
						'lfeditor'
					) . 'res/images/garbage.gif" title="' . $name . '" ' .
					'alt="' . $name . '" /> </a> </td>';

				// restore/diff
				if ($stateBool) {
					$name = $lang->getLL('function.backupMgr.recover');
					$content .= '<td class="bgColor4"> <a href="#" title="' . $name . '" ' .
						'onclick="submitBackupForm(\'' . $filename . '\', \'' . $langFile . '\', ' .
						'0, 1, 0, 0);"> <img src="' . t3lib_extMgm::extRelPath(
							'lfeditor'
						) . 'res/images/recover.gif" title="' . $name . '" ' .
						'alt="' . $name . '" /> </a> </td>';

					$name = $lang->getLL('function.backupMgr.diff.diff');
					$content .= '<td class="bgColor4"> <a href="#" title="' . $name . '" ' .
						'onclick="submitBackupForm(\'' . $filename . '\', \'' . $langFile . '\', ' .
						'0, 0, 0, 1);"> <img src="' . t3lib_extMgm::extRelPath(
							'lfeditor'
						) . 'res/images/diff.gif" title="' . $name . '" ' .
						'alt="' . $name . '" /> </a> </td>';
					$content .= '</tr>';
				} else {
					$content .= '<td class="bgColor4">[-]</td><td class="bgColor4">[-]</td>';
				}
			}
		}
		$content .= '</tbody></table>';

		return $content;
	}

	/**
	 * Generates and returns a module token for the form moduleCall and the action user_txlfeditorM1
	 *
	 * @return string
	 */
	protected static function generateModuleToken() {
		$token = '';
		if (t3lib_div::compat_version('6.2')) {
			$token = '\u0026moduleToken=' . \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get()->generateToken(
				'moduleCall', 'user_txlfeditorM1'
			);
		}

		return $token;
	}
}

// Default-Code for using XCLASS (dont touch)
if (defined(
		'TYPO3_MODE'
	) && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_template.php']
) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lfeditor/mod1/class.tx_lfeditor_mod1_template.php']);
}

?>