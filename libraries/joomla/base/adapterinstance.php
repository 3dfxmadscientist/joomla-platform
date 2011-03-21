<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Base
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

/**
 * Adapter Instance Class
 *
 * @package		Joomla.Platform
 * @subpackage	Base
 * @since		11.1
 */
class JAdapterInstance extends JObject {

	/**
	 * @var		object	Parent
	 * @since	11.1
	 */
	protected $parent = null;

	/**
	 * @var		object	Database
	 * @since	11.1
	 */
	protected $db = null;

	/**
	 * Constructor
	 *
	 * @param	object	$parent		Parent object [JAdapter instance]
	 * @param	object	$db			Database object [JDatabase instance]
	 * @param 	array	$options	Configuration Options
	 *
	 * @return	void
	 * @since	11.1
	 */
	public function __construct(&$parent, &$db, $options = Array())
	{
		// set the properties from the options array that is passed in
		$this->setProperties($options);

		// set the parent and db in case $options for some reason overrides it
		$this->parent = &$parent;
		$this->db = &$db ? $db : JFactory::getDBO(); // pull in the global dbo in case
	}

	/**
	 * Retrieves the parent object
	 *
	 * @return 	object parent
	 * @since 	11.1
	 */
	public function getParent()
	{
		return $this->parent;
	}
}