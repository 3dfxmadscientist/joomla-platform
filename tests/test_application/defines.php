<?php
/**
 * @version		$Id: defines.php 20196 2011-01-09 02:40:25Z ian $
 * @package		Joomla.Site
 * @subpackage	Application
 * @copyright	Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('JPATH_BASE') or die;

/**
 * Joomla! Application define.
 */

//Global definitions.
//Joomla framework path definitions.
$parts = explode(DS, JPATH_BASE);
array_pop($parts);
array_pop($parts);

//Defines.
define('JPATH_ROOT',			JPATH_BASE);
define('JPATH_CONFIGURATION',		JPATH_ROOT);
define('JPATH_LIBRARIES',		implode(DS, $parts));
define('JPATH_PLUGINS',			JPATH_ROOT.DS.'plugins');
define('JPATH_THEMES',			JPATH_BASE.DS.'templates');
define('JPATH_CACHE',			JPATH_BASE.DS.'cache');
define('JPATH_MANIFESTS',		JPATH_BASE.DS.'manifests');
