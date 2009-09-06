<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight webCMS
 * Copyright (C) 2005-2009 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at http://www.gnu.org/licenses/.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Calendar
 * @license    LGPL
 * @filesource
 */


/**
 * Class Events
 *
 * Provide methods to get all events of a certain period from the database.
 * @copyright  Leo Feyer 2005-2009
 * @author     Leo Feyer <leo@typolight.org>
 * @package    Controller
 */
abstract class Events extends Module
{

	/**
	 * Current URL
	 * @var string
	 */
	protected $strUrl;

	/**
	 * Today 00:00:00
	 * @var integer
	 */
	protected $intToday;

	/**
	 * Current events
	 * @var array
	 */
	protected $arrEvents = array();


	/**
	 * Sort out protected archives
	 * @param array
	 * @return array
	 */
	protected function sortOutProtected($arrCalendars)
	{
		if (BE_USER_LOGGED_IN)
		{
			return $arrCalendars;
		}

		$this->import('FrontendUser', 'User');
		$objCalendar = $this->Database->execute("SELECT id, protected, groups FROM tl_calendar WHERE id IN(" . implode(',', $arrCalendars) . ")");
		$arrCalendars = array();

		while ($objCalendar->next())
		{
			if ($objCalendar->protected)
			{
				$groups = deserialize($objCalendar->groups, true);

				if (!is_array($this->User->groups) || count($this->User->groups) < 1 || !is_array($groups) || count($groups) < 1)
				{
					continue;
				}

				if (count(array_intersect($groups, $this->User->groups)) < 1)
				{
					continue;
				}
			}

			$arrCalendars[] = $objCalendar->id;
		}

		return $arrCalendars;
	}


	/**
	 * Get all events of a certain period
	 * @param array
	 * @param integer
	 * @param integer
	 * @return array
	 */
	protected function getAllEvents($arrCalendars, $intStart, $intEnd)
	{
		if (!is_array($arrCalendars))
		{
			return array();
		}

		$this->import('String');
		$time = time();

		foreach ($arrCalendars as $id)
		{
			$strUrl = $this->strUrl;

			// Get current "jumpTo" page
			$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=(SELECT jumpTo FROM tl_calendar WHERE id=?)")
									  ->limit(1)
									  ->execute($id);

			if ($objPage->numRows)
			{
				$strUrl = $this->generateFrontendUrl($objPage->row(), '/events/%s');
			}

			// Get events of the current period
			$objEvents = $this->Database->prepare("SELECT *, (SELECT title FROM tl_calendar WHERE id=?) AS calendar, (SELECT name FROM tl_user WHERE id=author) author FROM tl_calendar_events WHERE pid=? AND ((startTime>=? AND startTime<=?) OR (endTime>=? AND endTime<=?) OR (startTime<=? AND endTime>=?) OR (recurring=1 AND (recurrences=0 OR repeatEnd>=?) AND startTime<=?))" . (!BE_USER_LOGGED_IN ? " AND (start='' OR start<?) AND (stop='' OR stop>?) AND published=1" : "") . " ORDER BY startTime")
										->execute($id, $id, $intStart, $intEnd, $intStart, $intEnd, $intStart, $intEnd, $intStart, $intEnd, $time, $time);

			if ($objEvents->numRows < 1)
			{
				continue;
			}

			while ($objEvents->next())
			{
				$this->addEvent($objEvents, $objEvents->startTime, $objEvents->endTime, $strUrl, $intEnd, $id);

				// Recurring events
				if ($objEvents->recurring)
				{
					$count = 0;
					$arrRepeat = deserialize($objEvents->repeatEach);

					while ($objEvents->endTime < $intEnd)
					{
						if ($objEvents->recurrences > 0 && $count++ >= $objEvents->recurrences)
						{
							break;
						}

						$arg = $arrRepeat['value'];
						$unit = $arrRepeat['unit'];

						if ($arg < 1)
						{
							break;
						}

						$strtotime = '+ ' . $arg . ' ' . $unit;

						$objEvents->startTime = strtotime($strtotime, $objEvents->startTime);
						$objEvents->endTime = strtotime($strtotime, $objEvents->endTime);

						// Skip events outside the scope
						if ($objEvents->startTime < $intStart || $objEvents->endTime > $intEnd)
						{
							continue;
						}

						$this->addEvent($objEvents, $objEvents->startTime, $objEvents->endTime, $strUrl, $intEnd, $id);
					}
				}
			}
		}

		// Sort data
		foreach (array_keys($this->arrEvents) as $key)
		{
			ksort($this->arrEvents[$key]);
		}

		// HOOK: modify result set
		if (isset($GLOBALS['TL_HOOKS']['getAllEvents']) && is_array($GLOBALS['TL_HOOKS']['getAllEvents']))
		{
			foreach ($GLOBALS['TL_HOOKS']['getAllEvents'] as $callback)
			{
				$this->import($callback[0]);
				$this->arrEvents = $this->$callback[0]->$callback[1]($this->arrEvents, $arrCalendars, $intStart, $intEnd);
			}
		}

		return $this->arrEvents;
	}


	/**
	 * Add an event to the array of active events
	 * @param object
	 * @param integer
	 * @param integer
	 * @param string
	 * @param integer
	 * @param string
	 * @param integer
	 */
	protected function addEvent(Database_Result $objEvents, $intStart, $intEnd, $strUrl, $intLimit, $intCalendar)
	{
		$intDate = $intStart;
		$intKey = date('Ymd', $intStart);
		$span = Calendar::calculateSpan($intStart, $intEnd);
		$strDate = $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $intStart);
		$strDay = $GLOBALS['TL_LANG']['DAYS'][date('w', $intStart)];
		$strMonth = $GLOBALS['TL_LANG']['MONTHS'][(date('n', $intStart)-1)];

		if ($span > 0)
		{
			$strDate = $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $intStart) . ' - ' . $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $intEnd);
			$strDay = '';
		}

		$strTime = '';

		if ($objEvents->addTime)
		{
			if ($span > 0)
			{
				$strDate = $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $intStart) . ' - ' . $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $intEnd);
			}
			elseif ($intStart == $intEnd)
			{
				$strTime = $this->parseDate($GLOBALS['TL_CONFIG']['timeFormat'], $intStart);
			}
			else
			{
				$strTime = $this->parseDate($GLOBALS['TL_CONFIG']['timeFormat'], $intStart) . ' - ' . $this->parseDate($GLOBALS['TL_CONFIG']['timeFormat'], $intEnd);
			}
		}

		// Store raw data
		$arrEvent = $objEvents->row();

		// Overwrite some settings
		$arrEvent['time'] = $strTime;
		$arrEvent['date'] = $strDate;
		$arrEvent['day'] = $strDay;
		$arrEvent['month'] = $strMonth;
		$arrEvent['parent'] = $intCalendar;
		$arrEvent['link'] = $objEvents->title;
		$arrEvent['title'] = specialchars($objEvents->title);
		$arrEvent['href'] = $this->generateEventUrl($objEvents, $strUrl);
		$arrEvent['target'] = ($objEvents->target ? LINK_NEW_WINDOW_BLUR : '');
		$arrEvent['class'] = strlen($objEvents->cssClass) ? ' ' . $objEvents->cssClass : '';
		$arrEvent['details'] = $this->String->encodeEmail($objEvents->details);
		$arrEvent['start'] = $intStart;
		$arrEvent['end'] = $intEnd;

		$this->arrEvents[$intKey][$intStart][] = $arrEvent;

		// Get today's date
		if (is_null($this->intToday))
		{
			$this->intToday = strtotime(date('Y-m-d') . ' 00:00:00');
		}

		// Multi-day event
		for ($i=1; $i<=$span && $intDate<=$intLimit; $i++)
		{
			// Only show first occurrence
			if ($this->cal_noSpan && (!$this instanceof ModuleUpcomingEvents || $intDate >= $this->intToday))
			{
				break;
			}

			$intDate = strtotime('+ 1 day', $intDate);
			$intNextKey = date('Ymd', $intDate);

			$this->arrEvents[$intNextKey][$intDate][] = $arrEvent;
		}
	}


	/**
	 * Generate a URL and return it as string
	 * @param object
	 * @param string
	 * @return string
	 */
	protected function generateEventUrl(Database_Result $objEvent, $strUrl)
	{
		// Link to default page
		if ($objEvent->source == 'default' || !strlen($objEvent->source))
		{
			return ampersand(sprintf($strUrl, ((!$GLOBALS['TL_CONFIG']['disableAlias'] && strlen($objEvent->alias)) ? $objEvent->alias : $objEvent->id)));
		}

		// Link to external page
		if ($objEvent->source == 'external')
		{
			$this->import('String');

			if (substr($objEvent->url, 0, 7) == 'mailto:')
			{
				$objEvent->url = 'mailto:' . $this->String->encodeEmail(substr($objEvent->url, 7));
			}

			return ampersand($objEvent->url);
		}

		// Fallback to current URL
		$strUrl = ampersand($this->Environment->request, true);

		// Get internal page
		$objPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
							 	  ->limit(1)
								  ->execute($objEvent->jumpTo);

		if ($objPage->numRows)
		{
			return ampersand($this->generateFrontendUrl($objPage->fetchAssoc()));
		}

		return '';
	}

}

?>