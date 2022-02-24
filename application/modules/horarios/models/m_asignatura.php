<?php

/**
 * Modelo de tabla asignatura
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_asignatura extends CI_Model {

	private
		$_table = 'asignatura';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Retorna arreglo con asignaturas
	 *
	 * @return array
	 */
	public function fetch()
	{
		$result = array();

		$query = $this->db
			->select('*', false)
			->from("{$this->_table}")
			->order_by('glosa', 'asc')
			->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		return $result;
	}

	/**
	 * Genera arreglo para utilizar en form_dropdown
	 *
	 * @return string
	 */
	public function dropdown_options()
	{
		$asignaturas = $this->fetch();
		$result = array('' => '--Asignatura--');

		foreach ($asignaturas as $asignatura) {
			$result[$asignatura->id] = $asignatura->glosa;
		}

		return $result;
	}

	/**
	 * Agrega una asignatura
	 *
	 * @param array $data array(glosa)
	 * @return int
	 */
	public function create($data)
	{
		$this->db->insert($this->_table, $data);

		return $this->db->insert_id();
	}

	/**
	 * Actualiza una asignatura
	 *
	 * @param int $id
	 * @param array $data array(glosa)
	 */
	public function update($id, $data)
	{
		$this->db->where('id', $id);
		return $this->db->update($this->_table, $data);
	}

	/**
	 * Elimina una asignatura
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	/**
	 * Retorna información de la asignatura
	 *
	 * @param int $id_asignatura
	 * @return array
	 */
	public function info($id_asignatura)
	{
		$q = <<<PQR
select *
from asignatura
where id = {$id_asignatura}
PQR;

		return $this->db->query($q)->row();
	}

	/**
	 * Indica si el nombre de una asignatura ya existe.
	 *
	 * @param str $glosa
	 *
	 * @return bool
	 */
	public function existe($glosa)
	{
		$q = <<<PQR
select count(*) cantidad
from asignatura
where glosa = '{$glosa}'
PQR;

		$r = $this->db->query($q)->row();

		return $r->cantidad == 1;
	}

}
