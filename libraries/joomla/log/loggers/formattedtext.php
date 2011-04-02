<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Log
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.log.logger');
jimport('joomla.filesystem.folder');

/**
 * Joomla! Formatted Text File Log class
 *
 * This class is designed to use as a base for building formatted text files for output. By
 * default it emulates the SysLog style format output. This is a disk based output format.
 *
 * @package     Joomla.Platform
 * @subpackage  Log
 * @since       11.1
 */
class JLoggerFormattedText extends JLogger
{
	/**
	 * @var    resource  The file pointer for the log file.
	 * @since  11.1
	 */
	protected $file;

	/**
	 * @var    string  The format for which each entry follows in the log file.  All fields must be named
	 *                 in all caps and be within curly brackets eg. {FOOBAR}.
	 * @since  11.1
	 */
	protected $format = '{DATETIME}	{PRIORITY}	{CATEGORY}	{MESSAGE}';

	/**
	 * @var    string  The full filesystem path for the log file.
	 * @since  11.1
	 */
	protected $path;

	/**
	 * Constructor.
	 *
	 * @param   array  $options  Log object options.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function __construct(array & $options)
	{
		// Call the parent constructor.
		parent::__construct($options);

		// Use the default entry format unless explicitly set otherwise.
		if (!empty($this->options['text_entry_format'])) {
			$this->format = (string) $this->options['text_entry_format'];
		}

		// The name of the text file defaults to 'error.php' if not explicitly given.
		if (empty($this->options['text_file'])) {
			$this->options['text_file'] = 'error.php';
		}

		// The name of the text file path defaults to that which is set in configuration if not explicitly given.
		if (empty($this->options['text_file_path'])) {
			$this->options['text_file_path'] = JFactory::getConfig()->get('log_path');
		}

		// Build the full path to the log file.
		$this->path = $this->options['text_file_path'].'/'.$this->options['text_file'];

		// If the file doesn't already exist we need to create it and generate the file header.
		if (!is_file($this->path)) {

			// Make sure the folder exists in which to create the log file.
			JFolder::create(dirname($this->path));

			// Build the log file header.
			$head[] = '#<?php die(\'Direct Access To Log Files Not Permitted\'); ?>';
			$head[] = '#Version: 1.0';
			$head[] = '#Date: '.gmdate('Y-m-d H:i:s').' UTC';
			$head[] = '#Software: '.JVersion::getLongVersion();
			$head[] = '';

			// Prepare the fields string
			$fields = strtolower(str_replace('}', '', str_replace('{', '', $this->format)));
			$head[] = '#Fields: '.$fields;
			$head[] = '';

			$head = implode("\n", $head);
		}
		else {
			$head = false;
		}

		// Open the file for writing (append mode).
		if (!$this->file = fopen($this->path, 'a')) {
			// Throw exception.
		}
		if ($head) {
			if (!fputs($this->file, $head)) {
				// Throw exception.
			}
		}
	}

	/**
	 * Destructor.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	public function __destruct()
	{
		if (is_resource($this->file)) {
			fclose($this->file);
		}
	}

	/**
	 * Method to add an entry to the log.
	 *
	 * @param   JLogEntry  The log entry object to add to the log.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public function addEntry(JLogEntry $entry)
	{
		// Set some default field values if not already set.
		if (!isset ($entry->clientIP)) {

			// Check for proxies as well.
			if (isset($_SERVER['REMOTE_ADDR'])) {
				$entry->clientIP = $_SERVER['REMOTE_ADDR'];
			}
			elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$entry->clientIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$entry->clientIP = $_SERVER['HTTP_CLIENT_IP'];
			}
		}

		// If the time field is missing or the date field isn't only the date we need to rework it.
		if ((strlen($entry->date) != 10) || !isset($entry->time)) {

			// Get the date and time strings in GMT.
			$entry->datetime = $entry->date->toISO8601();
			$entry->time = $entry->date->format('H:i:s', false);
			$entry->date = $entry->date->format('Y-m-d', false);
		}

		// Get a list of all the entry keys and make sure they are upper case.
		$tmp = array_change_key_case(get_object_vars($entry), CASE_UPPER);

		// Get all of the available fields in the format string.
		$fields = array();
		preg_match_all("/{(.*?)}/i", $this->format, $fields);

		// Fill in field data for the line.
		$line = $this->format;
		for ($i = 0; $i < count($fields[0]); $i++)
		{
			$line = str_replace($fields[0][$i], (isset($tmpentry[$fields[1][$i]])) ? $tmpentry[$fields[1][$i]] : '-', $line);
		}

		// Write the new entry to the file.
		if (!fputs($this->file, $line."\n")) {
			return false;
		}

		return true;
	}

	protected function foo()
	{
		// Build the full path to the log file.
		$this->path = $this->options['text_file_path'].'/'.$this->options['text_file'];

		// If the file doesn't already exist we need to create it and generate the file header.
		if (!is_file($this->path)) {

			// Make sure the folder exists in which to create the log file.
			JFolder::create(dirname($this->path));

			// Get the header for the log file.
			$head = $this->generateFileHeader();
		}
		else {
			$head = false;
		}
	}

	/**
	 * Method to generate the log file header.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function generateFileHeader()
	{
		// Initialize variables.
		$head = array();

		// Build the log file header.

		// If the no php flag is not set add the php die statement.
		if (empty($this->options['text_file_no_php'])) {
			$head[] = '#<?php die(\'Forbidden.\'); ?>';
		}
		$head[] = '#Date: '.gmdate('Y-m-d H:i:s').' UTC';
		$head[] = '#Software: '.JVersion::getLongVersion();
		$head[] = '';

		// Prepare the fields string
		$head[] = '#Fields: '.strtolower(str_replace('}', '', str_replace('{', '', $this->format)));
		$head[] = '';

		return implode("\n", $head);
	}
}
