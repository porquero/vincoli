<?php

/**
 * Modelo de tabla bloque
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_bloque extends CI_Model {

	private
					$_table = 'bloque';

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
						->from("{$this->_table} ")
						->order_by('id', 'asc')
						->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		return $result;
	}

	/**
	 *
	 * @param array $data array(rango, comentario)
	 * @return int
	 */
	public function create($data)
	{
		$this->db->insert($this->_table, $data);

		return $this->db->insert_id();
	}

	/**
	 * Actualiza un bloque
	 *
	 * @param int $id
	 * @param array $data array(rango, comentario)
	 */
	public function update($id, $data)
	{
		$this->db->where('id', $id);
		$this->db->update($this->_table, $data);
	}

	/**
	 * Elimina un bloque
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

}
