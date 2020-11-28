<?php

/**
 * Class for managing database queries.
 */

namespace App\Libraries\Database;

require_once 'Connection.php';

class Query {

	/**
	 * The Connection object.
	 *
	 * @access private
	 * @var object App\Libraries\Database
	 */
	private $connect_object;

	/**
	 * The actual connection itself.
	 *
	 * @access private
	 * @var object \mysqli
	 */
	private $connection;

	/**
	 * The statement to execute.
	 *
	 * @access private
	 * @var string
	 */
	private $statement;

	/**
	 * The query object.
	 *
	 * @access private
	 * @var object \mysqli_result | \mysqli_stmt
	 */
	private $query;
	
	/**
	 * The result set.
	 *
	 * @access private
	 * @var array
	 */
	private $result = array();

	/**
	 * Parameters for prepared statements.
	 *
	 * @access private
	 * @var array
	 */
	private $parameters = array();

	/**
	 * The number of rows returned by the query.
	 *
	 * @access private
	 * @var int
	 */
	private $num_rows = 0;

	/**
	 * The number of rows affected by the query.
	 *
	 * @access private
	 * @var int
	 */
	private $affected_rows = 0;

	/**
	 * The auto-generated insert ID.
	 *
	 * @access private
	 * @var int
	 */
	private $insert_id = 0;

	/**
	 * Constructor: setter for query properties.
	 *
	 * @access public
	 * @param object $connection App\Libraries\Database\Connection
	 * @param string $statement
	 * @param array $parameters (optional)
	 */
	public function __construct(Connection $connection, string $statement, array $parameters = array()) {
		# Connection dependency injection.
		$this->connect_object = $connection;
		$this->connection = $this->connect_object->getConnection();

		# Set the statement.
		$this->statement = $statement;

		# Set the parameters.
		$this->parameters = $parameters;
	}

	/**
	 * Setter for `connect_object`.
	 *
	 * @access public
	 * @param object $connect_object App\Libraries\Database\Connection
	 * @return void
	 */
	public function setConnectObject(Connection $connect_object) {
		$this->connect_object = $connect_object;
	}

	/**
	 * Setter for `statement`.
	 *
	 * @access public
	 * @param string $statement
	 * @return void
	 */
	public function setStatement(string $statement) {
		$this->statement = $statement;
	}

	/**
	 * Setter for `parameters`.
	 *
	 * @access public
	 * @param array $parameters
	 * @return void
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
	}

	/**
	 * Getter for `num_rows`.
	 *
	 * @access public
	 * @return int
	 */
	public function getNumRows(): int {
		return $this->num_rows;
	}

	/**
	 * Getter for `affected_rows`.
	 *
	 * @access public
	 * @return int
	 */
	public function getAffectedRows(): int {
		return $this->affected_rows;
	}

	/**
	 * Getter for `insert_id`.
	 *
	 * @access public
	 * @return int
	 */
	public function getInsertID(): int {
		return $this->insert_id;
	}

	/**
	 * Getter for `result`.
	 * Get the result as an associative array.
	 *
	 * @access public
	 * @return array
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Getter for `result`.
	 * Get the result as an object.
	 *
	 * @access public
	 * @return object
	 */
	public function getResultObject(): object {
		return (object) $this->result;
	}

	/**
	 * Determine if a result was returned.
	 *
	 * @access public
	 * @return bool
	 */
	public function hasResult(): bool {
		return count($this->result) > 0;
	}

	/**
	 * Detect if this is a parameterized (prepared) statement.
	 *
	 * @access public
	 * @return bool
	 */
	public function isParameterized(): bool {
		return !empty($this->parameters);
	}

	/**
	 * Bind parameters to a statement.
	 *
	 * @access private
	 * @return bool
	 */
	private function bindParams() {
		# Not a prepared statement.
		if(!$this->isParameterized()) return false;

		# Containers for data types and references.
		$data_types = '';
		$references = [];

		foreach ($this->parameters as $key => $value) {

			# Detect data types.
			if(is_int($value)) $data_types .= 'i';
			elseif(is_float($value)) $data_types .= 'd';
			elseif(is_string($value)) $data_types .= 's';
			else $data_types .= 'b';

			# Set references.
			$references[$key] = &$this->parameters[$key];
		}

		# Prepend data types to references.
		array_unshift($references, $data_types);

		# Bind the parameters.
		return call_user_func_array([$this->query, 'bind_param'], $references);
	}

	/**
	 * Prepare a statement for execution.
	 *
	 * @access private
	 * @return bool
	 */
	private function prepare(): bool {
		# Check the connection.
		if(!$this->connect_object->isConnected()) return false;

		if(false !== ($this->query = $this->connection->prepare($this->statement))) {
			
			# Bind parameters.
			if(!$this->bindParams()) return false;

			# Execute the statement.
			if(!$this->query->execute()) return false;

			# SELECT, SHOW, EXPLAIN and DESCRIBE operations.
			if(is_object($this->query->result_metadata())) {

				# Get the result.
				$result_object = $this->query->get_result();

				# Update `num_rows`.
				$this->num_rows = $result_object->num_rows;

				# Store the result.
				$this->store($result_object);

				# Free the result.
				$result_object->free();
			}

			# Update `affected_rows`.
			$this->affected_rows = $this->connection->affected_rows;

			# Update `insert_id`.
			$this->insert_id = $this->connection->insert_id;

			# Query was successful.
			return true;
		}

		# Query failed.
		return false;

	}

	/**
	 * Perform a query on the database.
	 *
	 * @access private
	 * @return bool
	 */
	private function query(): bool {
		# Check the connection.
		if(!$this->connect_object->isConnected()) return false;

		# Run the query.
		if(false !== ($this->query = $this->connection->query($this->statement))) {

			##
			# SELECT, SHOW, EXPLAIN or DESCRIBE.
			##

			if(is_object($this->query)) {

				# Update `num_rows`.
				$this->num_rows = $this->query->num_rows;

				# Store the result.
				$this->store($this->query);

				# Free the result.
				$this->query->free();
			}

			##
			# All other operations.
			##

			# Update `affected_rows`.
			$this->affected_rows = $this->connection->affected_rows;

			# Update `insert_id`.
			$this->insert_id = $this->connection->insert_id;

			# Query was successful.
			return true;
		}

		# Query failed.
		return false;
	}

	/**
	 * Execute the request.
	 *
	 * @access public
	 * @return object App\Database\Query
	 */
	public function execute(): Query {
		# Prepared statements.
		if($this->isParameterized()) $this->prepare();
	
		# Raw statements.
		else $this->query();

		# Get the current state.
		return $this;
	}

	/**
	 * Store the result.
	 *
	 * @access private
	 * @param object $result
	 * @return void
	 */
	private function store(object $result) {
		if($this->num_rows > 0) {
			$this->result = array();

			# Loop through and grab all results.
			while($row = $result->fetch_object())
				$this->result[] = $row;

		}
	}

}