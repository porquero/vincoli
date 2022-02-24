<?php

/**
 * Modelo de tabla plan_estudio
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_plan_estudio extends CI_Model {

	private
		$_table = 'plan_estudio';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Eimina el plan de estudio del curso.
	 *
	 * @param type $id_curso
	 */
	public function reset($id_curso)
	{
		$this->db->where('id_curso', $id_curso);
		$this->db->delete($this->_table);
	}

	/**
	 * Agrega el plan de estudio del curso.
	 *
	 * @param int $id_curso
	 * @param array $data array('id_curso', 'id_asignatura', 'horas');
	 * @return boolean
	 */
	public function create_batch($id_curso, $data)
	{
		$asignaturas = array();
		$this->reset($id_curso);

		foreach ($data as $k => $datos) {
			if (in_array($datos['id_asignatura'], $asignaturas) === TRUE) {
				unset($data[$k]);
			}
			$asignaturas[] = $datos['id_asignatura'];
		}

		if (count((array) $data) > 0) {
			return $this->db->insert_batch($this->_table, $data);
		}

		return TRUE;
	}

	/**
	 * Rescata de la bd el plan de estudio del curso según su id.
	 *
	 * @param type $id_curso
	 * @return type
	 */
	public function del_curso($id_curso)
	{
		$result = array();

		$query = $this->db->select('a.id id_asignatura, a.glosa, pe.horas, pe.id_profesor')
			->from($this->_table . ' pe')
			->join('asignatura a', 'pe.id_asignatura=a.id', 'left')
			->order_by('pe.horas', 'desc')
			->where('id_curso', $id_curso)
			->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		$query->free_result();

		return $result;
	}

	/**
	 * Retorna las horas totales del plan de estudio del curso.
	 * 
	 * @param int $id_curso
	 * @return int
	 */
	public function total_horas($id_curso)
	{
		$q = <<<PQR
SELECT SUM( horas ) horas
FROM plan_estudio
WHERE id_curso ={$id_curso}
PQR;

		$q = $this->db->query($q);
		$r = $q->row();

		return $r->horas;
	}

	/**
	 * Retorna las horas totales del plan de estudio del curso para la asignatura.
	 *
	 * @param int $id_curso
	 * @return int
	 */
	public function total_horas_asignatura($id_curso, $id_asignatura)
	{
		$q = <<<PQR
SELECT SUM( horas ) horas
FROM plan_estudio
WHERE id_curso ={$id_curso}
AND id_asignatura = {$id_asignatura}
PQR;

		$q = $this->db->query($q);
		$r = $q->row();

		return $r->horas;
	}

	/**
	 * Obtiene las asignaturas  con sus horas faltantes del plan de estudio del curso.
	 *
	 * @param type $id_curso
	 * @return type
	 */
	public function asignaturas_faltantes($id_curso)
	{
		$result = array();

		$q = <<<PQR
select horas, pe.id_asignatura, (horas - count(h.id_asignatura)) horas_faltantes, h.id id_horario
from plan_estudio pe
left outer join horarios h on pe.id_curso = h.id_curso and pe.id_asignatura = h.id_asignatura
where pe.id_curso = {$id_curso}
group by pe.id_curso, pe.id_asignatura
having horas_faltantes > 0
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[] = $row;
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Consulta si la asignatura ya existe en el plan de estudios del curso
	 *
	 * @param array $datos
	 * 
	 * @return bool
	 */
	public function asignatura_existe($id_curso, $id_asignatura)
	{
		$datos = array(
				'id_curso' => $id_curso,
				'id_asignatura' => $id_asignatura,
		);

		return $this->db->get_where($this->_table, $datos) !== FALSE;
	}

}
