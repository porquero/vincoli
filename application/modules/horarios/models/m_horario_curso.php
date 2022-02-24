<?php

/**
 * Description of m_horario_curso
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_horario_curso extends CI_Model {

	protected
					$_table = 'horario_curso';

	/**
	 * Carga la base de datos
	 */
	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Resetea el horario del curso y agrega el nuevo
	 *
	 * @param int $id_curso
	 * @param type $data array(array('id_curso', 'id_dia', 'id_bloque'), array('id_curso', 'id_dia', 'id_bloque'))
	 * @return boolean
	 */
	public function create_batch($id_curso, $data)
	{
		$this->reset($id_curso);
		if (count((array) $data) > 0) {
			return $this->db->insert_batch($this->_table, $data);
		}

		return TRUE;
	}

	/**
	 * Elimina el horario del curso
	 *
	 * @param type $id_curso
	 */
	public function reset($id_curso)
	{
		$this->db->where('id_curso', $id_curso);
		$this->db->delete($this->_table);
	}

	/**
	 * Retorna bloques del horario del curso
	 *
	 * @todo optimizar arreglo resultado: Ver pa posibilidad de retornar un objeto.s
	 *
	 * @param int $id_curso
	 * @return array
	 */
	public function horario_bloques($id_curso)
	{
		$result = array();

		$query = $this->db->select('pd.id_bloque, d.glosa')
						->from($this->_table . ' pd')
						->join('dia d', 'd.id=pd.id_dia')
						->where('pd.id_curso', $id_curso)
						->get();

		foreach ($query->result() as $row) {
			$result[] = array(
					'dia' => substr($row->glosa, 0, 2),
					'id_bloque' => $row->id_bloque,
					'id_check' => substr($row->glosa, 0, 2) . $row->id_bloque,
			);
		}

		return $result;
	}

}
