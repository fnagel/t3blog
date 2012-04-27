<?php
class tx_t3blog_pi2_addFieldsToFlexForm {

	/**
	 * fetch the available widgets
	 *
	 * @author 	kay stenschke <kstenschke@snowflake.ch>
	 * @param 	array 	$config
	 * @return	widget array
	 */
	public function getWidgets(array $config) {
		$widgets = $this->getWidgetDirectories();
		$this->updateTCEFormsItems($config, $widgets);

		return $config;
	}

	/**
	 * Obtains directories from t3blog and other extensions
	 *
	 * @return array Keys are widget keys, values are directories in EXT: or site-related format
	 */
	protected function getWidgetDirectories() {
		$defaultWidgetPath = t3lib_extMgm::extPath('t3blog', 'pi1/widgets');
		$folders = $this->getWidgetFolders($defaultWidgetPath, true);	// fetch list of contained folders
		$this->fetchExternalWidgets($folders);
		ksort($folders);

		return $folders;
	}

	/**
	 * Fetches widget list
	 *
	 * @return array
	 * @see tx_t3blog_pi2::fetchWidgetKeys()
	 */
	public function getWidgetList() {
		$widgets = $this->getWidgetDirectories();
		$widgetsArray = array(); // associative widgets array, for usage from other than ff-renderer to resolve the widgets' keys
		foreach($widgets as $widgetKey => $widgetDirectory)	{
			list($widgetTitle, $widgetDescription) = $this->fetchWidgetTitleAndDescription($widgetDirectory);
			$widgetsArray[$widgetKey] = array(
				'folder' => $widgetDirectory,
				'key' => $widgetKey,
				'title' => $widgetTitle,
				'desctiption' => $widgetDescription,
			);
		}
		return $widgetsArray;
	}

	/**
	 * Updates items in TCEforms config array
	 *
	 * @param array $config Config array (in and out)
	 * @param array $widgets Widgets
	 * @return void
	 */
	protected function updateTCEFormsItems(array &$config, array $widgets) {
		$widgetsArray = array();
		foreach ($widgets as $widgetKey => $folder)	{
			list($widgetTitle, $widgetDescription) = $this->fetchWidgetTitleAndDescription($folder);
			$optionsList[] = array(
				0 => $widgetTitle . ($widgetDescription ? ' &ndash; ' . $widgetDescription : ''),
				1 => $widgetKey
			);
		}
		$config['items'] = array_merge($config['items'], $optionsList);
	}

	/**
	 * Fetches folders for external widgets
	 *
	 * @param array $folders
	 * @return void
	 */
	protected function fetchExternalWidgets(array &$folders) {
		$params = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['getWidgets'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3blog']['getWidgets'] as $hookFunc) {
				$folderArray = t3lib_div::callUserFunction($hookFunc, $params, $this);
				if (is_array($folderArray)) {
					foreach ($folderArray as $widgetName => $folder) {
						if (!isset($folders[$widgetName])) {
							$testFolder = t3lib_div::getFileAbsFileName($folder);
							if (is_dir($testFolder)) {
								$folders[$widgetName] = rtrim($folder, '/');
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Fetch title and description for the widget from the locallang file
	 *
	 * @param 	string $pathToWidget
	 * @return 	array
	 */
	protected function fetchWidgetTitleAndDescription($pathToWidget) {
		$languageFile = $pathToWidget . '/locallang.xml';
		$languageKey = $BE_USER->uc['lang'] ? $BE_USER->uc['lang'] : 'default';
		$localLang = t3lib_div::readLLfile($languageFile, $languageKey);
		if (is_array($localLang) && isset($localLang[$languageKey])) {
			if (version_compare(TYPO3_branch, '4.6.0', '<')) {
				return array($localLang[$languageKey]['title'], $localLang[$languageKey]['widgetSelector.description']);
			}
			else {
				return array($localLang[$languageKey]['title'][0]['source'], $localLang[$languageKey]['widgetSelector.description'][0]['source']);
			}
		}

		return array('?', '?');
	}

	/**
	 * Fetch all widget subdirectories of given directory
	 *
	 * @param	string	$searchInFolder Absolute folder to scan
	 * @return	array 	Key is name, value is a path to the folder
	 */
	protected function getWidgetFolders($searchInFolder) {
		$files = array();

		$dir = opendir($searchInFolder);
		while (($file = readdir($dir)) !== false) {
			if ($file{0} != '.') {
				$testPath = $searchInFolder . '/' . $file;
				if (is_dir($testPath) && file_exists($testPath . '/locallang.xml')) {
					$files[$file] = $testPath;
				}
			}
		}
		closedir($dir);

		return $files;
	}
}

?>