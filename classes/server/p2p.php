<?php
class BEA_CSF_Server_P2P {

	/**
	 * Add connection on DB
	 */
	public static function merge( $connection, BEA_CSF_Synchronization $sync ) {
		return (array) $connection;
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 * 
	 * @param array $term
	 * @return \WP_Error|boolean
	 */
	public static function delete( $connection, BEA_CSF_Synchronization $sync ) {
		return (array) $connection;
	}

}