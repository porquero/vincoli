<?php

/**
 * Modelo de tabla curso
 *
 * @author porquero
 */
class m_profesor_disponibilidad extends CI_Model {

	private
					$_table = 'profesor_disponibilidad';

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

	/**
	 * Elimina los bloques disponibles del profesor
	 *
	 * @param type $id_profesor
	 */
	public function reset($id_profesor)
	{
		$this->db->where('id_profesor', $id_profesor);
		$this->db->delete($this->_table);
	}

	/**
	 * Resetea los bloques disponibles del profesor y agrega los nuevos
	 *
	 * @param integer $id_profesor
	 * @param type $data array(array('id_profesor', 'id_dia', 'id_bloque'), array('id_profesor', 'id_dia', 'id_bloque'))
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
	 * Actualiza los bloques del profesor.
	 *
	 * @param integer $id_profesor
	 * @param array $data
	 * @return boolean
	 */
	public function update($id_profesor, $data)
	{
		$this->reset($id_profesor);

		return $this->create($data);
	}

	/**
	 * Retorna disponibilidad del profesor por id
	 *
	 * @param integer $id_profesor
	 * @return array
	 */
	public function por_id($id_profesor)
	{
		$result = array();

		$query = $this->db->select('pd.id_bloque, d.glosa')
						->from($this->_table . ' pd')
						->join('dia d', 'd.id=pd.id_dia')
						->where('pd.id_profesor', $id_profesor)
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
