<?PHP

/**
 * Plugin 'T3BLOG' for the 't3blog' extension.
 * Widget class.calendar
 *
 * @author		carmine diluca <cdiluca@snowflake.ch>
 * @package		TYPO3
 * @subpackage	tx_t3blog
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

class calendar extends tslib_pibase {
	var $prefixId	  = 'calendar';		// Same as class name
	var $scriptRelPath = 'pi1/widgets/calendar/class.calendar.php';	// Path to this script relative to the extension dir.
	var $extKey		= 't3blog';	// The extension key.
	protected $globalPiVars;
	protected $day;

	/**
	 * The start day of the week. This is the day that appears in the first column
	 * of the calendar. Sunday = 0.
	 *
	 * @var int
	 */
	protected $startDay;

	/**
	 * The start month of the year. This is the month that appears in the first slot
	 * of the calendar in the year view. January = 1.
	 *
	 * @var int
	 */
	protected $startMonth;

	/**
	 * The labels to display for the days of the week. The first entry in this array
	 * represents Sunday.
	 *
	 * @var array
	 */
	protected $dayNames = array();

	/**
	 * The labels to display for the months of the year. The first entry in this array
	 * represents January.
	 *
	 * @var array
	 */
	protected $monthNames = array();

	/*
	 * The number of days in each month. You're unlikely to want to change this...
	 * The first entry in this array represents January.
	 *
	 * @var array
	 */
	protected $daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

	/**
	 * Main processing method of the class.
	 *
	 * @param string $content Unused
	 * @param array $conf Widget configuration
	 * @param array $piVars
	 * @return string
	 */
	public function main($content, array $conf, array $piVars){
		$this->globalPiVars = $piVars;
		$this->conf = $conf;
		$this->init();

		if($this->globalPiVars['blogList']['datefrom'])	{
			$year = substr($this->globalPiVars['blogList']['datefrom'], 0, 4);
			$month = str_replace('-', '', substr($this->globalPiVars['blogList']['datefrom'], 5, 2));
			$this->day = str_replace('-', '', substr($this->globalPiVars['blogList']['datefrom'], 8, 2));
		} elseif ($this->globalPiVars['blogList']['month'] && $this->globalPiVars['blogList']['year']) {
			$year = $this->globalPiVars['blogList']['year'];
			$month = $this->globalPiVars['blogList']['month'];
			$this->day = $this->globalPiVars['blogList']['day'];
		} elseif ($this->globalPiVars['blogList']['showUid']) {

			//If there is a show uid, but no specific date, take the date from the blog entry.

			$where = 'uid='.(int)$this->globalPiVars['blogList']['showUid'];
			list($blogentry) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*',
				'tx_t3blog_post', $where);
			if (is_array($blogentry)) {
				$this->day = date('j', $blogentry['date']);
				$month = date('n', $blogentry['date']);
				$year = date('Y', $blogentry['date']);
			}
		} else {
			$today = getdate(time());
			$this->day = $today['mday'];
			$month = $today['mon'];
			$year = $today['year'];
		}
		$this->day = intval($this->day);

		$data['calendar'] = $this->getMonthView(intval($month), intval($year));

		return t3blog_div::getSingle($data, 'calendaroutput', $this->conf);
	}

	/**
	 * Initial Method
	 */
	protected function init() {
		$this->localCobj = t3lib_div::makeInstance('tslib_cObj');
		$this->cObj = $this->localCobj;
		$this->pi_loadLL();

		// Initialize localized data
		$this->startDay = min(6, max(0, intval($this->conf['startDay'])));
		$this->startMonth = min(12, max(1, intval($this->conf['startMonth'])));

		for ($day = 0; $day < 7; $day++) {
			$this->dayNames[] = $this->pi_getLL('day_' . $day);
		}

		for ($month = 1; $month <= 12; $month++) {
			$this->monthNames[] = $this->pi_getLL('month_' . $month);
		}
	}

	/**
	* Return the URL to link to in order to display a calendar for a given month/year.
	* You must override this method if you want to activate the "forward" and "back"
	* feature of the calendar.
	*
	* Note: If you return an empty string from this function, no navigation link will
	* be displayed. This is the default behaviour.
	*
	* If the calendar is being displayed in "year" view, $month will be set to zero.
	*
	* @param	int		$month: number of the mount
	* @param	int		$year: number of the year
	*
	* @return	returns the url
	*/
	public function getCalendarLink($month, $year) {
		static $cache = array();

		$cacheKey = $month . '.' . $year;
		if (!isset($cache[$cacheKey])) {
			$data =  array(
				'month' => $month,
				'year' => $year
			);
			$cache[$cacheKey] = t3blog_div::getSingle($data, 'navLink', $this->conf);
		}
		return $cache[$cacheKey];
	}

	/**
	 * Return the URL to link to  for a given date.
	 * You must override this method if you want to activate the date linking feature of the calendar.
	 *
	 * Note: If you return an empty string from this function, no navigation link will be displayed. This is the default behaviour.
	 *
	 * @param 	int		$day: Day
	 * @param	int		$month: Month
	 * @param	int		$year: Year
	 *
	 * @return	String	Url to the dayview
	 */
	public function getDateLink($day, $month, $year){
		$datefrom = $year.'-'.$month.'-'.$day;
		return t3blog_div::getSingle(
			array(
				'day' => $day,
				'date'=> $datefrom,
				'blogUid' => t3blog_div::getBlogPid()
			), 'dateLink', $this->conf);
	}


	/**
	* Return the HTML for the current month
	*
	* @return 	returns html for the current month
	*/
	public function getCurrentMonthView(){
		$today = getdate(time());
		return $this->getMonthView($today['mon'], $today['year']);
	}


	/**
	* Return the HTML for the current year
	*
	* @param	returns html for the current year
	*/
	public function getCurrentYearView(){
		$d = getdate(time());
		return $this->getYearView($d["year"]);
	}


	/**
	* Return the HTML for a specified month
	*
	* @param	int		$month: specific month
	* @param	int		$year: specific year
	*
	* @return	the html for a specific month
	*/
	public function getMonthView($month, $year){
		return $this->getMonthHTML($month, $year, true);
	}


	/**
	* Return the HTML for a specified year
	*
	* @param	int		$year: specific year
	*
	* @return	the html for a specific year
	*/
	public function getYearView($year){
		return $this->getYearHTML($year);
	}

	/**
	* Calculate the number of days in a month, taking into account leap years.
	*
	* @param	int		$month: month
	* @param	int		$year: year
	*
	* @return	calculated number of days in a month
	*/
	protected function getDaysInMonth($month, $year){
		$daysInMonth = 0;
		if ($month >= 1 && $month <= 12) {
			if ($month == 2) {
				$daysInMonth = intval(date('d', mktime(0, 0, 0, 3, 0, $year)));
			}
			else {
				$daysInMonth = $this->daysInMonth[$month - 1];
			}
		}

		return $daysInMonth;
	}

	/**
	 * returns the month name from the locallang
	 *
	 * @param  	int		$monthNr: month
	 *
	 * @return 	month as string
	 */

	protected function getMonthName($monthNr){
		return $this->pi_getLL('month_' . intval($monthNr));
	}

	/**
	* Generate the HTML for a given month
	*
	* @param	int		$month: month
	* @param	int		$year: year
	* @param	int		$showYear: show or no show
	*
	* @return	html
	*/
	protected function getMonthHTML($month, $year, $showYear = false){
		$result = '';

		list($month, $year) = $this->adjustDate($month, $year);

		$header = $this->getMonthName($month);

		$prev = $this->adjustDate($month - 1, $year);
		$next = $this->adjustDate($month + 1, $year);

		if ($showYear) {
			$prevMonth = $this->getCalendarLink($prev[0], $prev[1]);
			$nextMonth = $this->getCalendarLink($next[0], $next[1]);
			$header .= ' ' . $year;
		}
		else {
			$prevMonth = $nextMonth = '';
		}

		$result .= "<table class=\"calendar\">\n";
		$result .= "<tr>\n";
		$result .= "<th class=\"previous navigation\">" . (($prevMonth == '') ? '&nbsp;' : '<a href="'.htmlspecialchars($prevMonth).'">'.$this->conf['prevString'].'</a> </th>');
		$result .= '<th colspan="5">' . htmlspecialchars($header) . '</th>';
		$result .= "<th class=\"next navigation\">" . (($nextMonth == '') ? '&nbsp;' : '<a href="'.htmlspecialchars($nextMonth).'">'.$this->conf['nextString'].'</a> </th>');
		$result .= "</tr>\n";
		$result .= "<tr class=\"month\">\n";
		$result .= "<td class=\" first \">" . htmlspecialchars($this->dayNames[($this->startDay)%7]). "</td>\n";
		$result .= "<td>" . htmlspecialchars($this->dayNames[($this->startDay+1)%7]) . "</td>\n";
		$result .= "<td>" . htmlspecialchars($this->dayNames[($this->startDay+2)%7]) . "</td>\n";
		$result .= "<td>" . htmlspecialchars($this->dayNames[($this->startDay+3)%7]) . "</td>\n";
		$result .= "<td>" . htmlspecialchars($this->dayNames[($this->startDay+4)%7]) . "</td>\n";
		$result .= "<td>" . htmlspecialchars($this->dayNames[($this->startDay+5)%7]) . "</td>\n";
		$result .= "<td class=\"last\">" . htmlspecialchars($this->dayNames[($this->startDay+6)%7]) . "</td>\n";
		$result .= "</tr>\n";

		// We need to work out what date to start at so that the first appears in the correct column
		$currentDay = $this->startDay + 1 - $this->getFirstWeekDayOfTheMonth($month, $year);
		while ($currentDay > 1) {
			$currentDay -= 7;
		}

		$today = getdate(time());

		$postStatistics = $this->getPostStatisticsForCurrentMonth($month, $year);
		$daysInMonth = $this->getDaysInMonth($month, $year);
		while ($currentDay <= $daysInMonth) {
			$result .= '<tr>';

			for ($weekDayNumber = 0; $weekDayNumber < 7; $weekDayNumber++) {
				$hasBlogEntries = isset($postStatistics[(string)$currentDay]);

				$classes = array();

				if ($daysInMonth - $currentDay < 7) {
					$classes[] = 'lastrow';
				}

				if ($weekDayNumber == 0) {
					$classes[] = 'first';
				} else if ($weekDayNumber == 6)	{
					$classes[] = 'last';
				}

				if ($year == $today['year'] && $month == $today['mon'] && $currentDay == $today['mday']) {
					$classes[] = 'calendarToday';
				} else {
					$classes[] = 'calendar';
				}

				if ($hasBlogEntries) {
					$classes[] = 'isBlogDay';
				}

				if ($this->day == $currentDay) {
					$classes[] = 'selectedBlogDay';
				}

				$result .= '<td ';
				if (count($classes) > 0) {
					$result .= 'class="' . htmlspecialchars(implode(' ', $classes)) . '" ';
				}
				$result .= 'align="right" valign="top">';

				if ($currentDay > 0 && $currentDay <= $daysInMonth) {
					$result .= $hasBlogEntries ? $this->getDateLink($currentDay, $month, $year) : $currentDay;
				} else {
					$result .= '&nbsp;';
				}
				$result .= "</td>\n";
				$currentDay++;
			}

			$result .= '</tr>';
		}
		$result .= '</table>';
		return $result;
	}

	/**
	 * Obtains the number of the first day of the month (0 == Sunday).
	 *
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	protected function getFirstWeekDayOfTheMonth($month, $year) {
		$date = getdate(mktime(12, 0, 0, $month, 1, $year));
		return $date['wday'];
	}

	/**
	 * Obtains staticstics about amount of posts in the current month.
	 *
	 * @param int $month
	 * @param int $year
	 * @return array
	 */
	protected function getPostStatisticsForCurrentMonth($month, $year) {
		$startTime = mktime(0, 0, 0, $month, 1, $year);
		$endTime = mktime(0, 0, -1, $month + 1, 1, $year);
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'count(*) AS counter,DAY(FROM_UNIXTIME(date)) as day',
			'tx_t3blog_post',
			'pid=' . t3blog_div::getBlogPid() . ' AND ' .
				'date>=' . $startTime . ' AND date<=' . $endTime .
				$this->cObj->enableFields('tx_t3blog_post'),
			'day', '', '', 'day'
		);
		return $rows;
	}

	/**
	* Generate the HTML for a given year
	*
	* @param	int		$year: year
	*
	* @return	html for a given year
	*/
	protected function getYearHTML($year) {
		$s = "";
		$prev = $this->getCalendarLink(0, $year - 1);
		$next = $this->getCalendarLink(0, $year + 1);

		$s .= "<table class=\"calendar\" border=\"0\">\n";
		$s .= "<tr>";
		$s .= "<td align=\"center\" valign=\"top\" align=\"left\">" . (($prev == "") ? "&nbsp;" : "<a href=\"".$prev."\">&lt;&lt;</a>")  . "</td>\n";
		$s .= "<td class=\"calendarHeader\" valign=\"top\" align=\"center\">" . (($this->startMonth > 1) ? $year . " - " . ($year + 1) : $year) ."</td>\n";
		$s .= "<td align=\"center\" valign=\"top\" align=\"right\">" . (($next == "") ? "&nbsp;" : "<a href=\"".$next."\">&gt;&gt;</a>")  . "</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(0 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(1 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(2 + $this->startMonth, $year) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(3 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(4 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(5 + $this->startMonth, $year) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(6 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(7 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(8 + $this->startMonth, $year) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "<tr>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(9 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(10 + $this->startMonth, $year) ."</td>\n";
		$s .= "<td class=\"calendar\" valign=\"top\">" . $this->getMonthHTML(11 + $this->startMonth, $year) ."</td>\n";
		$s .= "</tr>\n";
		$s .= "</table>\n";

		return $s;
	}

	/*

	*/
	/**
	 * Adjust dates to allow months > 12 and < 0. Just adjust the years appropriately.
	 * e.g. Month 14 of the year 2001 is actually month 2 of year 2002.
	 *
	 * @param	int		$month: Month
	 * @param	int		$year: Year
	 *
	 * @return 	int		First element is the month, second the year
	 */
	protected function adjustDate($month, $year) {
		if ($month < 1 || $month > 12) {
			list($month, $year) = explode('.', date('m.Y', mktime(0, 0, 0, $month, 1, $year)));
		}
		return array(intval($month), intval($year));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/calendar/class.calendar.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/t3blog/pi1/widgets/calendar/class.calendar.php']);
}

?>