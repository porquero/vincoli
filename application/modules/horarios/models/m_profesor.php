<?php

/**
 * Modelo de tabla profesor
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>|
 */
class m_profesor extends CI_Model {

	private
		$_table = 'profesor';

	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Retorna un arreglo con los profesores
	 *
	 * @return array
	 */
	public function fetch()
	{
		$result = array();

		$query = $this->db->select('p.id, concat(p.nombres, " ", p.apellidos) nombre_profesor,
                    p.nombres, p.apellidos, p.horas_contrato, p.horas_aula, p.horas_no_lectivas, p.horas_permanencia,
                    p.trabajo_tecnico, p.comentario, c.glosa curso', false)
			->from("{$this->_table} p")
			->join('curso c', 'c.id_profesor = p.id', 'left')
			->order_by('nombre_profesor', 'asc')
			->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		$query->free_result();

		return $result;
	}

	/**
	 * Genera arreglo para utilizar en form_dropdown
	 *
	 * @return string
	 */
	public function dropdown_options()
	{
		$profesores = $this->fetch();
		$result = array('' => '-- Profesor --');

		foreach ($profesores as $profesor) {
			$result[$profesor->id] = $profesor->nombre_profesor;
		}

		return $result;
	}

	/**
	 * Crea un profesor
	 *
	 * @param array $data array('nombres', 'apellidos', 'horas_contrato', 'horas_aula', 'horas_no_lectivas',
	 *  'horas_permanencia', 'trabajo_tecnico','comentario')
	 * @return int
	 */
	public function create($data)
	{
		$this->db->insert($this->_table, $data);

		return $this->db->insert_id();
	}

	/**
	 * Actualiza un profesor
	 *
	 * @param array $data array('nombres', 'apellidos', 'horas_contrato', 'horas_aula', 'horas_no_lectivas',
	 *  'horas_permanencia', 'trabajo_tecnico','comentario')
	 * @param int $id
	 */
	public function update($id, $data)
	{
		$this->db->where('id', $id);
		return $this->db->update($this->_table, $data);
	}

	/**
	 * Eimina un profesor
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->db->where('id', $id);
		$this->db->delete($this->_table);
	}

	/**
	 * Entrega los bloques que el profesor informa como disponibles
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function mapa_disponibilidad($id_profesor)
	{
		$result = array();

		$q = $this->db->select()
			->from('disponibilidad_bloques db')
			->where('id_profesor', $id_profesor)
			->get();

		foreach ($q->result() as $row) {
			$result[$row->id_dia][$row->id_bloque] = TRUE;
		}

		$q->free_result();

		return $result;
	}

	/**
	 * Entrega los actuales bloques disponibles del profesor
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function mapa_disponibilidad_actual($id_profesor)
	{
		$result = array();

		$q = $this->db->select()
			->from('profesores_disponibles db')
			->where('id_profesor', $id_profesor)
			->get();

		foreach ($q->result() as $row) {
			$result[$row->id_dia][$row->id_bloque] = TRUE;
		}

		$q->free_result();

		return $result;
	}

	/**
	 * Retorna el horario del profesor
	 *
	 * @param int $id_profesor
	 * @return array
	 */
	public function horario($id_profesor)
	{
		$result = array();

		$q = <<<PQR
select h.*, c.glosa curso, a.glosa asignatura
from horarios h
left join curso c on c.id = h.id_curso
left join asignatura a on a.id = h.id_asignatura
where h.id_profesor = {$id_profesor}
union
select null, null, id_bloque, id_dia, null, id_profesor, null, null, null
from profesores_disponibles
where id_profesor = {$id_profesor}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[$row->id_dia][$row->id_bloque] = $row;
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna la info del profesor
	 *
	 * @param int $id_profesor
	 * @return obj
	 */
	public function info($id_profesor)
	{
		$q = <<<PQR
select p.*, c.glosa curso
from profesor p
left join curso c on c.id_profesor = p.id
where p.id = {$id_profesor}
PQR;

		return $this->db->query($q)->row();
	}

	/**
	 * Query para obtener las asignaturas del profesor.
	 *
	 * @param int $id_profesor
	 * @return str
	 */
	private function _asignaturas_query($id_profesor)
	{
		return <<<PQR
select a.*
from asignatura a
join profesor_has_asignatura pha on pha.id_asignatura = a.id
where id_profesor = {$id_profesor}
PQR;
	}

	/**
	 * Retorna las asignaturas del profesor que están en el plan de estudio del curso.
	 *
	 * @param int $id_profesor
	 * @param int $id_curso
	 */
	public function asignaturas_curso($id_profesor, $id_curso)
	{
		$result = array();

		$q = <<<PQR
SELECT a.id id_asignatura, a.glosa from asignatura a
join plan_estudio pe on pe.id_asignatura = a.id and pe.id_curso = {$id_curso}
join profesor_has_asignatura pha on pha.id_asignatura = pe.id_asignatura
join profesor p on p.id = pha.id_profesor
where p.id = {$id_profesor}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[] = $row->glosa;
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna asignaturas del profesor
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function asignaturas($id_profesor)
	{
		$result = array();

		$r = $this->db->query($this->_asignaturas_query($id_profesor));

		foreach ($r->result() as $row) {
			$result[] = $row->glosa;
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna asignaturas del profesor en formato string.
	 *
	 * @param type $id_profesor
	 *
	 * @return type
	 *
	 * @todo Revisar esta función. Al parecer está de más.
	 */
	public function asignaturas_txt($id_profesor)
	{
		$result = '';

		$r = $this->db->query($this->_asignaturas_query($id_profesor));

		foreach ($r->result() as $row) {
			$result .= " {$row->glosa} |";
		}

		$r->free_result();

		return preg_replace('/\|$/', '', $result);
	}

	/**
	 * Retorna cursos del profesor
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function cursos($id_profesor, $arreglo = FALSE)
	{
		$q = <<<PQR
select c.*
from curso c
join profesor_has_curso phc on phc.id_curso = c.id
where phc.id_profesor = {$id_profesor}
PQR;

		$r = $this->db->query($q);

		if ($arreglo === TRUE) {
			$result = array();
			foreach ($r->result() as $row) {
				$result[] = $row;
			}

			$r->free_result();

			return $result;
		}
		else {
			$result = '';
			foreach ($r->result() as $row) {
				$result .= " {$row->glosa} |";
			}

			$r->free_result();

			return preg_replace('/\|$/', '', $result);
		}

	}

	/**
	 * Entrega los profesores para la asignatura del curso para ser utilizados en form_dropdown().
	 *
	 * @param int $id_curso
	 * @param int $id_asignatura
	 * @return array
	 */
	public function para_curso_asignatura($id_curso, $id_asignatura)
	{
		$result = array();

if($id_curso === NULL || $id_asignatura === NULL) return false;

		$q = <<<PQR
select p.id, concat(p.nombres, " ", p.apellidos) nombre_profesor
from profesor_has_curso phc
join profesor_has_asignatura pha on pha.id_profesor = phc.id_profesor
join profesor p on p.id = phc.id_profesor
where pha.id_asignatura = {$id_asignatura} and phc.id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[$row->id] = $row->nombre_profesor;
		}

		$r->free_result();

		return $result;
	}

}
