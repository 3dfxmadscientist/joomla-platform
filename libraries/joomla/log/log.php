<?php
/**
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @package     Joomla.Platform
 * @subpackage  Log
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.log.logentry');
jimport('joomla.log.logformat');

// @deprecated  11.2
jimport('joomla.filesystem.path');

/**
 * Joomla! Log Class
 *
 * This class hooks into the global log configuration
 * settings to allow for user configured logging events to be sent
 * to where the user wishes it to be sent. On high load sites
 * SysLog is probably the best (pure PHP function), then the text
 * file based formats (CSV, W3C or plain FormattedText) and finally
 * MySQL offers the most features (e.g. rapid searching) but will incur
 * a performance hit due to INSERT being issued.
 *
 * @package     Joomla.Platform
 * @subpackage  Log
 * @since       11.1
 */
class JLog
{
	/**
	 * The format object for logging.
	 *
	 * @var    JLogFormat
	 * @since  11.1
	 */
	protected $format;

	/**
	 * Options array for the JLog instance.
	 *
	 * @var    array
	 * @since  11.1
	 */
	protected $options = array();

	/**
	 * Container for JLog instances.
	 *
	 * @var    array
	 * @since  11.1
	 */
	private static $_instances = array();

	/**
	 * Constructor.
	 *
	 * @param   array  $options  Log object options.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function __construct(array $options)
	{
		// The default format is the W3C logfile format.
		if (empty($options['format'])) {
			$options['format'] = 'w3c';
		}
		$options['format'] = strtolower($options['format']);

		// Set the options for the class.
		$this->options = array_merge($this->options, $options);

		// Attempt to instantiate the format object.
		try {
			$class = 'JLogFormat'.ucfirst($options['format']);
			$this->format = new $class($this->options);
		}
		catch (Exception $e) {
			jexit(JText::_('Unable to create a JLog instance: ').$e->getMessage());
		}
	}

	/**
	 * Returns a reference to the a JLog object, only creating it
	 * if it doesn't already exist.
	 *
	 * This method must be invoked as:
	 * 		<pre>$log = JLog::getInstance($options);</pre>
	 *
	 * @param   array       $options  The object configuration array.
	 * @param   deprecated  $arg2     Formerly the object configuration array.
	 * @param   deprecated  $arg3     Formerly the base path for the log file.
	 *
	 * @return	JLog
	 *
	 * @since	11.1
	 */
	public static function getInstance($options = array(), $arg2 = null, $arg3 = null)
	{
		// Get the system configuration object.
		$config = JFactory::getConfig();

		// Determine if we are dealing with a deprecated usage of JLog::getInstance();
		if (is_string($options)) {

			// Deprecation warning.
			JError::raiseWarning(100, 'JLog::getInstance() now accepts one options array.');

			// Fix up arguments.
			$file		= $options;
			$options	= $arg2;
			$path		= $arg3;

			// Set default path if not set and sanitize it.
			if (!$path) {
				$path = $config->get('log_path');
			}

			// Fix up the options so that we use the w3c format.
			$options['text_entry_format'] = $options['format'];
			$options['text_file'] = $file;
			$options['text_file_path'] = $path;
			$options['format'] = 'w3c';
		}

		// If no options were explicitly set use the default from configuration.
		if (empty ($options)) {
			$options = $config->getValue('log_options');
		}

		// Generate a unique signature for the JLog instance based on its options.
		$signature = md5(serialize($options));

		if (empty (self::$instances[$signature])) {
			// Attempt to instantiate the object.
			try {
				self::$instances[$signature] = new JLog($options);
			}
			catch (Exception $e) {
				jexit(JText::_('Unable to create a JLog instance: ').$e->getMessage());
			}
		}

		return self::$instances[$signature];
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
	public function add(JLogEntry $entry)
	{
		return $this->format->addEntry($entry);
	}

	/**
	 * Method to add an entry to the log file.
	 *
	 * @param       array    Array of values to map to the format string for the log file.
	 *
	 * @return      boolean  True on success.
	 *
	 * @deprecated  11.2
	 * @since       11.1
	 */
	public function addEntry($entry)
	{
		// Deprecation warning.
		JError::raiseWarning(100, 'JLog::addEntry() is deprecated, use JLog::add() instead.');

		// Easiest case is we already have a JLogEntry or Exception object to add.
		if ($entry instanceof JLogEntry) {
			return $this->add($entry);
		}
		// We have either an object or array that needs to be converted to a JLogEntry.
		elseif (is_array($entry) || is_object($entry)) {
			$tmp = new JLogEntry();
			foreach ((array) $entry as $k => $v)
			{
				switch ($k)
				{
					case 'c-ip':
						$tmp->clientIP = $v;
						break;
					case 'status':
						$tmp->category = $v;
						break;
					case 'level':
						$tmp->priority = $v;
						break;
					case 'comment':
						$tmp->message = $v;
						break;
					default:
						$tmp->$k = $v;
						break;
				}
			}
		}
		// Unrecognized type.
		else {
			return false;
		}

		return $this->add($tmp);
	}

	/**
	 * Method to register all of the log format classes with the system autoloader.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	private function _registerFormats()
	{
		// Define the expected folder in which to find log format classes.
		$formatsFolder = dirname(__FILE__).'/formats';

		// Ignore the operation if the formats folder doesn't exist.
		if (is_dir($formatsFolder)) {

			// Open the formats folder.
			$d = dir($formatsFolder);

			// Iterate through the folder contents to search for format classes.
			while (false !== ($entry = $d->read()))
			{
				// Only load for php files.
				if (is_file($entry) && (substr($entry, strrpos($entry, '.') + 1) == 'php')) {

					// Get the name and full path for each file.
					$name = preg_replace('#\.[^.]*$#', '', $entry);
					$path = $formatsFolder.'/'.$entry;

					// Register the class with the autoloader.
					JLoader::register('JLogFormat'.ucfirst($name), $path);
				}
			}

			// Close the formats folder.
			$d->close();
		}
	}
}
