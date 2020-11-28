<?php

/**
 * Class for managing database connections.
 */

namespace App\Libraries\Database;

class Connection {

	/**
	 * Server hostname.
	 *
	 * @access private
	 * @var string
	 */
	private $hostname;

	/**
	 * User name to access the server.
	 *
	 * @access private
	 * @var string
	 */
	private $username;

	/**
	 * Password to access the server.
	 *
	 * @access private
	 * @var string
	 */
	private $password;

	/**
	 * Database name.
	 *
	 * @access private
	 * @var string
	 */
	private $database;

	/**
	 * The connection.
	 *
	 * @access private
	 * @var object
	 */
	private $connection;

	/**
	 * Constructor: setter for connection properties.
	 *
	 * @access public
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 */
	public function __construct(string $hostname, string $username, string $password, string $database) {
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
	}

	/**
	 * Setter for `hostname`.
	 *
	 * @access public
	 * @param string $hostname
	 * @return void
	 */
	public function setHostname(string $hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * Setter for `username`.
	 *
	 * @access public
	 * @param string $username
	 * @return void
	 */
	public function setUsername(string $username) {
		$this->username = $username;
	}

	/**
	 * Setter for `password`.
	 *
	 * @access public
	 * @param string $password
	 * @return void
	 */
	public function setPassword(string $password) {
		$this->password = $password;
	}

	/**
	 * Setter for `database`.
	 *
	 * @access public
	 * @param string $database
	 * @return void
	 */
	public function setDatabase(string $database) {
		$this->database = $database;
	}

	/**
	 * Getter for `connection`.
	 *
	 * @access public
	 * @return object
	 */
	public function getConnection(): object {
		return $this->connection;
	}

	/**
	 * Verify that a connection exists.
	 *
	 * @access public
	 * @return bool
	 */
	public function isConnected(): bool {
		return $this->connection instanceof \mysqli && $this->connection->ping();
	}

	/**
	 * Attempt to open a connection.
	 *
	 * @access public
	 * @return bool
	 */
	public function open(): bool {
		# A connection does not already exist.
		if(!$this->isConnected()) {

			# Set the connection.
			$this->connection = new \mysqli($this->hostname, $this->username, $this->password, $this->database);

			# Get the connection status.
			return $this->isConnected();
		}

		# A connection already exists.
		return true;
	}

	/**
	 * Load the client character set.
	 *
	 * @access public
	 * @param string $char_set
	 * @return bool
	 */
	public function loadCharacterSet(string $char_set) {
		return $this->connection->set_charset($charset);
	}

	/**
	 * Close the connection.
	 *
	 * @access public
	 * @return bool
	 */
	public function close() {
		# A connection can not be closed if it doesn't exist!
		if($this->isConnected()) {

			# Get the current thread ID.
			$thread_id = $this->connection->thread_id;

			# Ask the server to kill the thread.
			$this->connection->kill($thread_id);

			# Close the connection.
			return $this->connection->close();
		}

		# Nothing to see here.
		return true;
	}
}