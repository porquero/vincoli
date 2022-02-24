<?php

/**
 * Modelo de tabla disponibilidad_especialidad
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_disponibilidad_especialidad extends CI_Model {

	private
					$_profesor_has_curso = 'profesor_has_curso',
					$_profesor_has_asignatura = 'profesor_has_asignatura';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Eimina todas las especialidades del profesor.
	 *
	 * @param int $id_profesor
	 */
	public function reset($id_profesor)
	{
		$this->db->trans_start();

		// Elimina cursos del profesor.
		$this->db->where('id_profesor', $id_profesor);
		$this->db->delete($this->_profesor_has_curso);

		// Elimina asignaturas del profesor.
		$this->db->where('id_profesor', $id_profesor);
		$this->db->delete($this->_profesor_has_asignatura);

		return $this->db->trans_complete();
	}

	/**
	 * Agrega todas las especialidades del profesor.
	 *
	 * @param int $id_profesor
	 * @param array $data_curso array(array('id_profesor', 'id_curso'));
	 * @param array $data_asignatira array('id_profesor', 'id_asignatura');
	 * @return boolean
	 */
	public function create_batch($id_profesor, $data_curso, $data_asignatura)
	{
		$this->db->trans_start();

		$this->reset($id_profesor);

		$this->_create_all_batch($id_profesor, $this->_profesor_has_curso, $data_curso);
		$this->_create_all_batch($id_profesor, $this->_profesor_has_asignatura, $data_asignatura);

		return $this->db->trans_complete();
	}

	/**
	 * Agrega las especialidades del profesor.
	 *
	 * @param int $id_profesor
	 * @param string $table
	 * @param array $data
	 * @return boolean
	 */
	private function _create_all_batch($id_profesor, $table, $data)
	{
		if (count((array) $data) > 0) {
			return $this->db->insert_batch($table, $data);
		}

		return FALSE;
	}

	/**
	 * Rescata de la bd la especialidad del profesor según su id.
	 * @todo optimizar arreglo resultado: Ver pa posibilidad de retornar un objeto.s
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function por_id($id_profesor)
	{
		$result = array();

		// Rescata cursos.
		$query = $this->db->select()
						->from($this->_profesor_has_curso)
						->where('id_profesor', $id_profesor)
						->get();

		foreach ($query->result() as $row) {
			$result['cursos'][] = array(
					'id_curso' => $row->id_curso,
			);
		}

		// Rescata asignaturas.
		$query = $this->db->select()
						->from($this->_profesor_has_asignatura)
						->where('id_profesor', $id_profesor)
						->get();

		foreach ($query->result() as $row) {
			$result['asignaturas'][] = array(
					'id_asignatura' => $row->id_asignatura,
			);
		}

		return $result;
	}

}
