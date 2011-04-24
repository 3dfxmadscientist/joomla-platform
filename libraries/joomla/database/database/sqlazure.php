<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Database
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

jimport('joomla.database.database');
jimport('joomla.utilities.string');

JLoader::register('JDatabaseQuerySQLAzure', dirname(__FILE__).'/sqlazurequery.php');

/**
 * SQL Server database driver
 *
 * @package     Joomla.Platform
 * @subpackage  Database
 * @since       11.1
 */
class JDatabaseSQLAzure extends JDatabase
{
	/**
	 * @var    string  The name of the database driver.
	 * @since  11.1
	 */
	public $name = 'sqlzure';

	/**
	 * @var    string  The character(s) used to quote SQL statement names such as table names or field names,
	 *                 etc.  The child classes should define this as necessary.  If a single character string the
	 *                 same character is used for both sides of the quoted name, else the first character will be
	 *                 used for the opening quote and the second for the closing quote.
	 * @since  11.1
	 */
	protected $nameQuote;

	/**
	 * @var    string  The null or zero representation of a timestamp for the database driver.  This should be
	 *                 defined in child classes to hold the appropriate value for the engine.
	 * @since  11.1
	 */
	protected $nullDate = '1900-01-01 00:00:00';

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function __construct($options)
	{
		// Get some basic values from the options.
		$options['host']     = (isset($options['host'])) ? $options['host'] : 'localhost';
		$options['user']     = (isset($options['user'])) ? $options['user'] : '';
		$options['password'] = (isset($options['password'])) ? $options['password'] : '';
		$options['database'] = (isset($options['database'])) ? $options['database'] : '';
		$options['select']   = (isset($options['select'])) ? (bool) $options['select'] : true;

		// Build the connection configuration array.
		$config = array(
			'Database' => $options['database'],
			'uid' => $options['user'],
			'pwd' => $options['password'],
			'CharacterSet' => 'UTF-8',
			'ReturnDatesAsStrings' => true
		);

		// Make sure the SQLSRV extension for PHP is installed and enabled.
		if (!function_exists('sqlsrv_connect')) {

			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {
				$this->errorNum = 1;
				$this->errorMsg = JText::_('JLIB_DATABASE_ERROR_ADAPTER_SQLSRV');
				return;
			}
			else {
				throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_ADAPTER_SQLSRV'));
			}
		}

		// Attempt to connect to the server.
		if (!($this->connection = @ sqlsrv_connect($options['host'], $config))) {

			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {
				$this->errorNum = 2;
				$this->errorMsg = JText::_('JLIB_DATABASE_ERROR_CONNECT_SQLSRV');
				return;
			}
			else {
				throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_CONNECT_SQLSRV'));
			}
		}

		// Make sure that DB warnings are not returned as errors.
		sqlsrv_configure('WarningsReturnAsErrors', 0);

		// Finalize initialisation
		parent::__construct($options);

		// If auto-select is enabled select the given database.
		if ($options['select'] && !empty($options['database'])) {
			$this->select($options['database']);
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
		if (is_resource($this->connection)) {
			sqlsrv_close($this->connection);
		}
	}

	/**
	 * Test to see if the SQLSRV connector is available.
	 *
	 * @return  bool  True on success, false otherwise.
	 *
	 * @since   11.1
	 */
	public static function test()
	{
		return (function_exists('sqlsrv_connect'));
	}

	/**
	 * Determines if the connection to the server is active.
	 *
	 * @return  bool  True if connected to the database engine.
	 *
	 * @since   11.1
	 */
	public function connected()
	{
		// TODO: Run a blank query here
		return true;
	}

	/**
	 * Method to get a JDate object represented as a datetime string in a format recognized by the database server.
	 *
	 * @param   JDate   $date   The JDate object with which to return the datetime string.
	 * @param   bool    $local  True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string  The datetime string in the format recognized for the database system.
	 *
	 * @since   11.1
	 */
	public function dateToString($date, $local = false)
	{
		return $date->format('Y-m-d H:i:s', $local);
	}

	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return  integer  The number of affected rows.
	 *
	 * @since   11.1
	 */
	public function getAffectedRows()
	{
		return sqlsrv_rows_affected($this->cursor);
	}

	/**
	 * Method to get the database collation in use by sampling a text field of a table in the database.
	 *
	 * @return  mixed  The collation in use by the database or boolean false if not supported.
	 *
	 * @since   11.1
	 */
	public function getCollation()
	{
		// TODO: Not fake this
		return 'MSSQL UTF-8 (UCS2)';
	}

	/**
	 * Method to escape a string for usage in an SQL statement.
	 *
	 * The escaping for MSSQL isn't handled in the driver though that would be nice.  Because of this we need
	 * to handle the escaping ourselves.  This is a first crack at it based on research done by Hooduku.
	 *
	 * @param   string  The string to be escaped.
	 * @param   bool    Optional parameter to provide extra escaping.
	 *
	 * @return  string  The escaped string.
	 *
	 * @since   11.1
	 */
	public function getEscaped($text, $extra = false)
	{
		// TODO: MSSQL Compatible escaping
		$result = addslashes($text);
		$result = str_replace("\'", "''", $result);
		$result = str_replace('\"', '"', $result);
		//$result = str_replace("\\", "''", $result);

		if ($extra) {
			// We need the below str_replace since the search in sql server doesnt recognize _ character.
			$result = str_replace('_', '[_]', $result);
		}

		return $result;
	}

	/**
	 * Gets an exporter class object.
	 *
	 * @return  JDatbaseExporterSQLAzure  An exporter object.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getExporter()
	{
		// Make sure we have an exporter class for this driver.
		if (!class_exists('JDatbaseExporterSQLAzure')) {
			throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_EXPORTER'));
		}

		$o = new JDatbaseExporterSQLAzure;
		$o->setDbo($this);

		return $o;
	}

	/**
	 * Gets an importer class object.
	 *
	 * @return  JDatbaseImporterSQLAzure  An importer object.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getImporter()
	{
		// Make sure we have an importer class for this driver.
		if (!class_exists('JDatbaseImporterSQLAzure')) {
			throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_IMPORTER'));
		}

		$o = new JDatbaseImporterSQLAzure;
		$o->setDbo($this);

		return $o;
	}

	/**
	 * Get the number of returned rows for the previous executed SQL statement.
	 *
	 * @param   resource  $cursor  An optional database cursor resource to extract the row count from.
	 *
	 * @return  integer   The number of returned rows.
	 *
	 * @since   11.1
	 */
	public function getNumRows($cursor = null)
	{
		return sqlsrv_num_rows($cursor ? $cursor : $this->cursor);
	}

	/**
	 * Get the current or query, or new JDatabaseQuery object.
	 *
	 * @param   bool   $new  False to return the last query set, True to return a new JDatabaseQuery object.
	 *
	 * @return  mixed  The current value of the internal SQL variable or a new JDatabaseQuery object.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getQuery($new = false)
	{
		if ($new) {
			// Make sure we have a query class for this driver.
			if (!class_exists('JDatbaseQuerySQLAzure')) {
				throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_MISSING_QUERY'));
			}
			return new JDatbaseQuerySQLAzure;
		}
		else {
			return $this->sql;
		}
	}

	/**
	 * Shows the table CREATE statement that creates the given tables.
	 *
	 * This is unsupported by MSSQL.
	 *
	 * @param   mixed  $tables  A table name or a list of table names.
	 *
	 * @return  array  A list of the create SQL for the tables.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getTableCreate($tables)
	{
		return '';
	}

	/**
	 * Retrieves field information about the given tables.
	 *
	 * @param   mixed  $tables    A table name or a list of table names.
	 * @param   bool   $typeOnly  True to only return field types.
	 *
	 * @return  array  An array of fields by table.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getTableFields( $tables, $typeOnly = true )
	{
		// Initialise variables.
		$result = array();

		// Sanitize input to an array and iterate over the list.
		settype($tables, 'array');
		foreach ($tables as $table)
		{
			// Set the query to get the table fields statement.
			$this->setQuery(
				'SELECT column_name as Field, data_type as Type, is_nullable as \'Null\', column_default as \'Default\'' .
				' FROM information_schema.columns' .
				' WHERE table_name = '.$this->quote($table)
			);
			$fields = $this->loadObjectList();

			// If we only want the type as the value add just that to the list.
			if ($typeOnly) {
				foreach ($fields as $field)
				{
					$result[$table][$field->Field] = preg_replace("/[(0-9)]/",'', $field->Type);
				}
			}
			// If we want the whole field data object add that to the list.
			else {
				foreach ($fields as $field)
				{
					$result[$table][$field->Field] = $field;
				}
			}
		}

		return $result;
	}

	/**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function getTableList()
	{
		// Set the query to get the tables statement.
		$this->setQuery('SELECT name FROM sysobjects WHERE xtype = \'U\';');
		$tables = $this->loadResultArray();

		return $tables;
	}

	/**
	 * Determines if the database engine supports UTF-8 character encoding.
	 *
	 * @return  boolean  True if supported.
	 *
	 * @since   11.1
	 */
	public function hasUTF()
	{
		return true;
	}

	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  integer  The value of the auto-increment field from the last inserted row.
	 *
	 * @since   11.1
	 */
	public function insertid()
	{
		// TODO: SELECT IDENTITY
		$this->setQuery('SELECT @@IDENTITY');
		return (int) $this->loadResult();
	}

	/**
	 * Inserts a row into a table based on an object's properties.
	 *
	 * @param   string  $table   The name of the database table to insert into.
	 * @param   object  $object  A reference to an object whose public properties match the table fields.
	 * @param   string  $key     The name of the primary key. If provided the object property is updated.
	 *
	 * @return  bool    True on success.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function insertObject($table, & $object, $key = null)
	{
		// Initialise variables.
		$fields = array();
		$values = array();

		// Create the base insert statement.
		$statement = 'INSERT INTO '.$this->nameQuote($table).' (%s) VALUES (%s)';

		// Iterate over the object variables to build the query fields and values.
		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process non-null scalars.
			if (is_array($v) or is_object($v) or $v === null) {
				continue;
			}

			// Ignore any internal fields.
			if ($k[0] == '_') {
				continue;
			}

			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->nameQuote($k);
			$values[] = $this->isQuoted($k) ? $this->quote($v) : (int) $v;
		}

		// Set the query and execute the insert.
		$this->setQuery(sprintf($statement, implode(',', $fields) ,  implode(',', $values)));
		if (!$this->query()) {
			return false;
		}

		// Update the primary key if it exists.
		$id = $this->insertid();
		if ($key && $id) {
			$object->$key = $id;
		}

		return true;
	}

	/**
	 * Method to get the first row of the result set from the database query as an associative array
	 * of ['field_name' => 'row_value'].
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadAssoc()
	{
		// Initialise variables.
		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get the first row from the result set as an associative array.
		if ($array = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_ASSOC)) {
			$ret = $array;
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an associative array
	 * of ['field_name' => 'row_value'].  The array of rows can optionally be keyed by a field name, but defaults to
	 * a sequential numeric array.
	 *
	 * NOTE: Chosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key     The name of a field on which to key the result array.
	 * @param   string  $column  An optional column name. Instead of the whole row, only this column value will be in
	 *                           the result array.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadAssocList($key = null, $column = null)
	{
		// Initialise variables.
		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get all of the rows from the result set.
		while ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_ASSOC))
		{
			$value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
			if ($key) {
				$array[$row[$key]] = $value;
			}
			else {
				$array[] = $value;
			}
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $array;
	}

	/**
	 * Method to get the next row in the result set from the database query as an object.
	 *
	 * @param   string  $class  The class name to use for the returned row object.
	 *
	 * @return  mixed   The result of the query as an array, false if there are no more rows.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadNextObject($class = 'stdClass')
	{
		static $cursor;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return $this->errorNum ? null : false;
		}

		// Get the next row from the result set as an object of type $class.
		if ($row =  sqlsrv_fetch_object($cursor, $class)) {
			return $row;
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);
		$cursor = null;

		return false;
	}

	/**
	 * Method to get the next row in the result set from the database query as an array.
	 *
	 * @return  mixed  The result of the query as an array, false if there are no more rows.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadNextRow()
	{
		static $cursor;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return $this->errorNum ? null : false;
		}

		// Get the next row from the result set as an object of type $class.
		if ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_NUMERIC)) {
			return $row;
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);
		$cursor = null;

		return false;
	}

	/**
	 * Method to get the first row of the result set from the database query as an object.
	 *
	 * @param   string  $class  The class name to use for the returned row object.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadObject($class = 'stdClass')
	{
		// Initialise variables.
		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get the first row from the result set as an object of type $class.
		if ($object = sqlsrv_fetch_object($cursor, $class)) {
			$ret = $object;
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an object.  The array
	 * of objects can optionally be keyed by a field name, but defaults to a sequential numeric array.
	 *
	 * NOTE: Chosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key    The name of a field on which to key the result array.
	 * @param   string  $class  The class name to use for the returned row objects.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadObjectList($key='', $class = 'stdClass')
	{
		// Initialise variables.
		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get all of the rows from the result set as objects of type $class.
		while ($row = sqlsrv_fetch_object($cursor, $class))
		{
			if ($key) {
				$array[$row->$key] = $row;
			}
			else {
				$array[] = $row;
			}
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $array;
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query.
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadResult()
	{
		// Initialise variables.
		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get the first row from the result set as an array.
		if ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_NUMERIC)) {
			$ret = $row[0];
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
	 * the database query.
	 *
	 * @param   integer  $offset  The row offset to use to build the result array.
	 *
	 * @return  mixed    The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadResultArray($offset = 0)
	{
		// Initialise variables.
		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get all of the rows from the result set as arrays.
		while ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_NUMERIC))
		{
			$array[] = $row[$offset];
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $array;
	}

	/**
	 * Method to get the first row of the result set from the database query as an array.  Columns are indexed
	 * numerically so the first column in the result set would be accessible via <var>$row[0]</var>, etc.
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadRow()
	{
		// Initialise variables.
		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get the first row from the result set as an array.
		if ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_NUMERIC)) {
			$ret = $row;
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an array.  The array
	 * of objects can optionally be keyed by a field offset, but defaults to a sequential numeric array.
	 *
	 * NOTE: Chosing to key the result array by a non-unique field can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key  The name of a field on which to key the result array.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function loadRowList($key=null)
	{
		// Initialise variables.
		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->query())) {
			return null;
		}

		// Get all of the rows from the result set as arrays.
		while ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_NUMERIC))
		{
			if ($key !== null) {
				$array[$row[$key]] = $row;
			}
			else {
				$array[] = $row;
			}
		}

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		return $array;
	}

	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function query()
	{
		if (!is_resource($this->connection)) {

			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {

				if ($this->debug) {
					JError::raiseError(500, 'JDatabaseDriverSQLAzure::query: '.$this->errorNum.' - '.$this->errorMsg);
				}
				return false;
			}
			else {
				JLog::add(JText::sprintf('JLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), JLog::ERROR, 'database');
				throw new DatabaseException();
			}
		}

		// Take a local copy so that we don't modify the original query and cause issues later
		$sql = $this->replacePrefix((string) $this->sql);
		if ($this->limit > 0 || $this->offset > 0) {
			$sql = $this->_limit($sql, $this->limit, $this->offset);
		}

		// If debugging is enabled then let's log the query.
		if ($this->debug) {

			// Increment the query counter and add the query to the object queue.
			$this->count++;
			$this->log[] = $sql;

			JLog::add($sql, JLog::DEBUG, 'databasequery');
		}

		// Reset the error values.
		$this->errorNum = 0;
		$this->errorMsg = '';

		// sqlsrv_num_rows requires a static or keyset cursor.
		if (JString::startsWith(ltrim(strtoupper($sql)), 'SELECT')) {
			$array = array('Scrollable' => SQLSRV_CURSOR_KEYSET);
		}
		else {
			$array = array();
		}

		// Execute the query.
		$this->cursor = sqlsrv_query($this->connection, $sql, array(), $array);

		// If an error occurred handle it.
		if (!$this->cursor) {

			// Populate the errors.
			$errors = sqlsrv_errors();
			$this->errorNum = $errors[0]['SQLSTATE'];
			$this->errorMsg = $errors[0]['message'].'SQL='.$sql;

			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {

				if ($this->debug) {
					JError::raiseError(500, 'JDatabaseDriverSQLAzure::query: '.$this->errorNum.' - '.$this->errorMsg);
				}
				return false;
			}
			else {
				JLog::add(JText::sprintf('JLIB_DATABASE_QUERY_FAILED', $this->errorNum, $this->errorMsg), JLog::ERROR, 'databasequery');
				throw new DatabaseException();
			}
		}

		return $this->cursor;
	}

	/**
	 * Select a database for use.
	 *
	 * @param   string  $database  The name of the database to select for use.
	 *
	 * @return  bool  True if the database was successfully selected.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function select($database)
	{
		if (!$database) {
			return false;
		}

		if (!sqlsrv_query($this->connection, 'USE '.$database, null, array('scrollable' => SQLSRV_CURSOR_STATIC))) {

			// Legacy error handling switch based on the JError::$legacy switch.
			// @deprecated  11.3
			if (JError::$legacy) {
				$this->errorNum = 3;
				$this->errorMsg = JText::_('JLIB_DATABASE_ERROR_DATABASE_CONNECT');
				return false;
			}
			else {
				throw new DatabaseException(JText::_('JLIB_DATABASE_ERROR_DATABASE_CONNECT'));
			}
		}

		return true;
	}

	/**
	 * Set the connection to use UTF-8 character encoding.
	 *
	 * @return  bool  True on success.
	 *
	 * @since   11.1
	 */
	public function setUTF()
	{
		// TODO: Remove this?
	}

	/**
	 * Method to commit a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function transactionCommit()
	{
		$this->setQuery('COMMIT TRANSACTION');
		$this->query();
	}

	/**
	 * Method to roll back a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function transactionRollback()
	{
		$this->setQuery('ROLLBACK TRANSACTION');
		$this->query();
	}

	/**
	 * Method to initialize a transaction.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function transactionStart()
	{
		$this->setQuery('START TRANSACTION');
		$this->query();
	}

	/**
	 * Updates a row in a table based on an object's properties.
	 *
	 * @param   string  $table   The name of the database table to update.
	 * @param   object  $object  A reference to an object whose public properties match the table fields.
	 * @param   string  $key     The name of the primary key.
	 * @param   bool    $nulls   True to update null fields or false to ignore them.
	 *
	 * @return  bool    True on success.
	 *
	 * @since   11.1
	 * @throws  DatabaseException
	 */
	public function updateObject($table, & $object, $key, $nulls=false)
	{
		// Initialise variables.
		$fields = array();
		$where  = '';

		// Create the base update statement.
		$statement = 'UPDATE '.$this->nameQuote($table).' SET %s WHERE %s';

		// Iterate over the object variables to build the query fields/value pairs.
		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process scalars that are not internal fields.
			if (is_array($v) or is_object($v) or $k[0] == '_') {
				continue;
			}

			// Set the primary key to the WHERE clause instead of a field to update.
			if ($k == $key) {
				$where = $this->nameQuote($k).'='.$this->quote($v);
				continue;
			}

			// Prepare and sanitize the fields and values for the database query.
			if ($v === null) {
				// If the value is null and we want to update nulls then set it.
				if ($nulls) {
					$val = 'NULL';
				}
				// If the value is null and we do not want to update nulls then ignore this field.
				else {
					continue;
				}
			}
			// The field is not null so we prep it for update.
			else {
				$val = $this->isQuoted($k) ? $this->quote($v) : (int) $v;
			}

			// Add the field to be updated.
			$fields[] = $this->nameQuote($k).'='.$val;
		}

		// We don't have any fields to update.
		if (empty($fields)) {
			return true;
		}

		// Set the query and execute the update.
		$this->setQuery(sprintf($statement, implode(",", $fields) , $where));
		return $this->query();
	}

	/**
	 * Method to fetch a row from the result set cursor as an array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.1
	 */
	protected function fetchArray($cursor = null)
	{
		return sqlsrv_fetch_array($cursor ? $cursor : $this->cursor, SQLSRV_FETCH_NUMERIC);
	}

	/**
	 * Method to fetch a row from the result set cursor as an associative array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.1
	 */
	protected function fetchAssoc($cursor = null)
	{
		return sqlsrv_fetch_array($cursor ? $cursor : $this->cursor, SQLSRV_FETCH_ASSOC);
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
	 * @param   string  $class   The class name to use for the returned row object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 *
	 * @since   11.1
	 */
	protected function fetchObject($cursor = null, $class = 'stdClass')
	{
		return sqlsrv_fetch_object($cursor ? $cursor : $this->cursor, $class);
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function freeResult($cursor = null)
	{
		sqlsrv_free_stmt($cursor ? $cursor : $this->cursor);
	}

	/**
	 * Diagnostic method to return explain information for a query.
	 *
	 * @return      string  The explain output.
	 *
	 * @since       11.1
	 * @deprecated  11.2
	 * @see         http://msdn.microsoft.com/en-us/library/aa259203%28SQL.80%29.aspx
	 */
	public function explain()
	{
		// Deprecation warning.
		JLog::add('JDatabase::explain() is deprecated.', JLog::WARNING, 'deprecated');

		// Backup the current query so we can reset it later.
		$backup = $this->sql;

		// SET SHOWPLAN_ALL ON - will make sqlsrv to show some explain of query instead of run it
		$this->setQuery('SET SHOWPLAN_ALL ON');
		$this->query();

		// Execute the query and get the result set cursor.
		$this->setQuery($backup);
		if (!($cursor = $this->query())) {
			return null;
		}

		// Build the HTML table.
		$first = true;
		$buffer = '<table id="explain-sql">';
		$buffer .= '<thead><tr><td colspan="99">'.$this->getQuery().'</td></tr>';
		while ($row = sqlsrv_fetch_array($cursor, SQLSRV_FETCH_ASSOC))
		{
			if ($first) {
				$buffer .= '<tr>';
				foreach ($row as $k => $v)
				{
					$buffer .= '<th>'.$k.'</th>';
				}
				$buffer .= '</tr></thead>';
				$first = false;
			}
			$buffer .= '<tbody><tr>';
			foreach ($row as $k => $v)
			{
				$buffer .= '<td>'.$v.'</td>';
			}
			$buffer .= '</tr>';
		}
		$buffer .= '</tbody></table>';

		// Free up system resources and return.
		sqlsrv_free_stmt($cursor);

		// Remove the explain status.
		$this->setQuery('SET SHOWPLAN_ALL OFF');
		$this->query();

		// Restore the original query to it's state before we ran the explain.
		$this->sql = $backup;

		return $buffer;
	}

	/**
	 * Get the version of the database connector.
	 *
	 * @return      string  The database connector version.
	 *
	 * @since       11.1
	 * @deprecated  11.2
	 */
	public function getVersion()
	{
		//TODO: Don't hardcode this.
		return '5.1.0';
	}

	/**
	 * Execute a query batch.
	 *
	 * @return      mixed  A database resource if successful, false if not.
	 *
	 * @since       11.1
	 * @deprecated  11.2
	 */
	public function queryBatch($abortOnError=true, $transactionSafe = false)
	{
		// Deprecation warning.
		JLog::add('JDatabase::queryBatch() is deprecated.', JLog::WARNING, 'deprecated');

		$sql = $this->replacePrefix((string) $this->sql);
		$this->errorNum = 0;
		$this->errorMsg = '';

		// If the batch is meant to be transaction safe then we need to wrap it in a transaction.
		if ($transactionSafe) {
			$this->_sql = 'BEGIN TRANSACTION;'.$this->sql.'; COMMIT TRANSACTION;';
		}

		$queries = $this->splitSql($sql);
		$error = 0;
		foreach ($queries as $query)
		{
			$query = trim($query);

			if ($query != '') {
				$this->cursor = sqlsrv_query($this->connection, $query, null, array('scrollable' => SQLSRV_CURSOR_STATIC));
				if ($this->_debug) {
					$this->count++;
					$this->log[] = $query;
				}
				if (!$this->cursor) {
					$error = 1;
					$errors = sqlsrv_errors();
					$this->errorNum = $errors[0]['sqlstate'];
					$this->errorMsg = $errors[0]['message'];

					if ($abortOnError) {
						return $this->cursor;
					}
				}
			}
		}
		return $error ? false : true;
	}

	/**
	 * Method to check and see if a field exists in a table.
	 *
	 * @param   string  $table  The table in which to verify the field.
	 * @param   string  $field  The field to verify.
	 *
	 * @return  bool    True if the field exists in the table.
	 *
	 * @since   11.1
	 */
	private function _checkFieldExists($table, $field)
	{
		$table = $this->replacePrefix((string) $table);
		$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS".
 				" WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$field'".
 				" ORDER BY ORDINAL_POSITION";
		$this->setQuery($sql);

		if ($this->loadResult()) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Method to wrap an SQL statement to provide a LIMIT and OFFSET behavior for scrolling through a result set.
	 *
	 * @param   string   $sql     The SQL statement to process.
	 * @param   integer  $offset  The affected row offset to set.
	 * @param   integer  $limit   The maximum affected rows to set.
	 *
	 * @return  string   The processed SQL statement.
	 *
	 * @since   11.1
	 */
	private function _limit($sql, $limit, $offset)
	{
		$orderBy = stristr($sql, 'ORDER BY');
		if (is_null($orderBy) || empty($orderBy)) {
			$orderBy = 'ORDER BY (select 0)';
		}
		$sql = str_ireplace($orderBy, '', $sql);

		$rowNumberText = ',ROW_NUMBER() OVER ('.$orderBy.') AS RowNumber FROM ';

		$sql = preg_replace('/\\s+FROM/','\\1 '.$rowNumberText.' ', $sql, 1);
		$sql = 'SELECT TOP '.$this->limit.' * FROM ('.$sql.') _myResults WHERE RowNumber > '.$this->offset;

		return $sql;
	}
}
