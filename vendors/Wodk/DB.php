<?php
/*
 * This file is part of Wodk.
 *
 * (c) 2012 Wilson Wise
 *
 * Extends MySQLi.
 *
 */
class Wodk_DB extends mysqli {
	private $_prefix = '';
	private $prefix_pattern = '/{{(\w+)}}/';
	private $use_prefix = FALSE;
	private $queries = array();

	/**
	 * Constructor, same as mysqli, but with error handling
	 */
	public function __construct($host = NULL, $user = NULL, $pass = NULL, $dbname = NULL, $port = NULL, $socket = NULL) {
		parent::__construct($host, $user, $pass, $dbname, $port, $socket);

		if ($this->connect_error) {
			die(sprintf('Connect Error (%s): %s', $this->connect_errno, $this->connect_error));
		}
	}
	
	/**
	 * qry
	 * This will execute a SQL statement, but is extended to take arguments ala printf
	 * @param The first argument is the SQL statement.
	 * @params The following arguments are items to be formatted into the SQL statement.
	 * @return The query with all argments formatted.
	 */
	public function qry() {
		$args = func_get_args();
		$query = $this->prepareQuery($args);
		return parent::query($query);
	}
	
	/**
	 * getInsertId
	 * @return Returns the insert id from the last INSERT query
	 */
	public function getInsertId() {
        return $this->insert_id;
    }
	
	/**
	 * setPrefix
	 * Will set the prefix to be used when formatting a query. Will determine if 
	 * we need to use a prefix based on what is passed in.
	 * @param $prefix A string to use a the prefix for tables in SQL statements.
	 * @return The instance for chaining.
	 */
	public function setPrefix($prefix = '') {
		$this->use_prefix = empty($prefix) ? FALSE : TRUE;
		$this->_prefix = $prefix;
		return $this;
	}
	
	/**
	 * setPrefixPattern
	 * Set the Regular Expression to used for prefix replacement.
	 * The default RegEx is /{{(\w+)}}/.
	 * @param $pattern A string of a RegEx pattern. 
	 * @return The instance for chaining.
	 */
	public function setPrefixPattern($pattern = '/{{(\w+)}}/') {
		$this->prefix_pattern = $pattern;
		return $this;
	}
    
    /**
     * setQuery
     * Set a "prepared" query for use later. This allows you to prepare queries
     * for use later.
     * @param The name of the query for use later.
     * @param The SQL query
     * @params The arguments to be formatted into the query 
     * @return The instance for chaining.
     */
    public function setQuery() {
    	$args = func_get_args();
    	$name = array_shift($args);
    	$query = $this->prepareQuery($args);
    	$this->queries[$name] = $query;
    	return $this;
    }
    
    /**
     * unsetQuery
     * Delete a "prepared" query.
     * @param $name The name of the query to remove.
     * @return The instance for chaining.
     */
	public function unsetQuery($name) {
    	unset($this->queries[$name]);
    	return $this;
    }
    
    /**
     * getQuery
     * Get a "prepared" query.
     * @param $name The name of the query to return.
     * @return The formatted query.
     */
    public function getQuery($name) {
    	return $this->queries[$name];
    }
    
    /**
     * useQuery
     * Call a previously "prepared" query.
     * @param $name The name of the query to use.
     */
    public function useQuery($name) {
		return parent::query($this->queries[$name]);
    }
    
    /**
     * formatQuery
     * Public "interface" for prepareQuery
     * @param The SQL query
     * @params The arguments to be formatted into the query
     * @return Formatted query
     */
    public function formatQuery() {
    	$args = func_get_args();
    	return $this->prepareQuery($args);
    }
    
    /**
     * prepareQuery
     * Do the formatting of a query.
     * @param $args An array of items to format. First item is the SQL statement. 
     * 				Remaining items are to be formatted into SQL statement.
     * @return The formatted query.
     */
    private function prepareQuery($args) {
		$query = array_shift($args);
		if ($this->use_prefix) {
			$query = preg_replace($this->prefix_pattern, $this->_prefix.'$1', $query);
		}		
		if (empty($args)) {
			$args = array();
		}
		else {
			$args = is_array($args[0]) ? $args[0] : $args;
		}
		$query = vsprintf($query, $args);
		return $query;
    }
    
}
?>