<?php

/**
 * Modelo de tabla día.
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_dia extends CI_Model {

	private
					$_table = 'dia';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Retorna arreglo con los bloques
	 *
	 * @return array
	 */
	public function fetch()
	{
		$result = array();

		$query = $this->db
						->select('*')
						->from("{$this->_table}")
						->order_by('id', 'asc')
						->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		return $result;
	}

}
