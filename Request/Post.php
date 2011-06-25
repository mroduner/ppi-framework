<?php

class PPI_Request_Post extends PPI_Request_Abstract {

	/**
	 * Obtain information from POST. This can be passed in or it defaults to $_POST
	 *
	 * @param array $post
	 */
	public function __construct(array $post = array()) {
		if(!empty($post)) {
			$this->_isCollected = false;
			$this->_array = $post;
		} else {
			$this->_array = $_POST;
		}
	}
}