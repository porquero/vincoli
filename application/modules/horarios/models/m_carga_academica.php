<?php

/**
 * Modelo de tabla carga_academica
 *
 * @author porquero
 */
class m_carga_academica extends CI_Model {

	private
					$_table = 'carga_academica';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Eimina todas las cargas académicas del profesor.
	 *
	 * @param type $id_profesor
	 */
	public function reset($id_profesor)
	{
		$this->db->where('id_profesor', $id_profesor);
		$this->db->delete($this->_table);
	}

	/**
	 * Agrega todas las cargas académicas del profesor.
	 *
	 * @param integer $id_profesor
	 * @param array $data array('id_profesor', 'id_curso', 'id_asignatura', 'horas');
	 * @return boolean
	 */
	public function create_batch($id_profesor, $data)
	{
		$this->reset($id_profesor);
		if (count((array) $data) > 0) {
			return $this->db->insert_batch($this->_table, $data);
		}

		return TRUE;
	}

	/**
	 * Rescata de la bd la carga académica del profesor según su id.
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function por_id($id_profesor)
	{
		$result = array();

		$query = $this->db->select()
						->from($this->_table)
						->where('id_profesor', $id_profesor)
						->get();

		foreach ($query->result() as $row) {
			$result[] = array(
					'id_curso' => $row->id_curso,
					'id_asignatura' => $row->id_asignatura,
					'horas' => $row->horas,
			);
		}

		return $result;
	}

}
