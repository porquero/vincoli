<?php

/**
 * Modelo de tabla curso
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_curso extends CI_Model {

	private
		$_table = 'curso';

	/**
	 * Carga la base de datos
	 */
	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Retorna arreglo con los cursos
	 *
	 * @return array
	 */
	public function fetch()
	{
		$result = array();

		$this->db
			->select('c.*, concat(p.nombres, " ", p.apellidos) profesor_jefe', false)
			->from("{$this->_table} c")
			->join('profesor p', 'p.id=c.id_profesor')
			->order_by('id_nivel, glosa', 'asc');

		$query = $this->db->get();

		foreach ($query->result() as $row) {
			$result[] = $row;
		}

		$query->free_result();

		return $result;
	}

	/**
	 * Retorna info del curso
	 *
	 * @param type $id_curso
	 * @return type
	 */
	public function info($id_curso)
	{
		$q = <<<PQR
select * from curso
where id = {$id_curso}
PQR;

		return $this->db->query($q)->row();
	}

	/**
	 * Genera arreglo para utilizar en form_dropdown
	 *
	 * @return string
	 */
	public function dropdown_options()
	{
		$cursos = $this->fetch();
		$result = array('' => '--Curso--');

		foreach ($cursos as $curso) {
			$result[$curso->id] = $curso->glosa;
		}

		return $result;
	}

	/**
	 *
	 * @param type $data array('glosa', 'id_profesor', 'comentario')
	 * @return int
	 */
	public function create($data)
	{
		$this->db->insert($this->_table, $data);

		return $this->db->insert_id();
	}

	/**
	 * Actualiza un curso
	 *
	 * @param int $id
	 * @param array $data array('glosa', 'id_profesor', 'comentario')
	 */
	public function update($id, $data)
	{
		$this->db->where('id', $id);
		return $this->db->update($this->_table, $data);
	}

	/**
	 * Elimina un profesor
	 *
	 * @param int $id
	 */
	public function delete($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete($this->_table);
	}

	/**
	 * Retorna bloques libres con los profesores disponibles para estos del curso.
	 * Si se envían datos del día y bloque, retorna los profesores disponibles para dichos.
	 *
	 * @param int $id_curso
	 * @param int $id_dia opcional
	 * @param int $id_bloque opcional
	 *
	 * @return array
	 */
	public function bloques_con_profesores_disponibles($id_curso, $id_dia = NULL, $id_bloque = NULL)
	{
		$result = array();
		$bloques = '';

		if (is_null($id_dia) === FALSE && is_null($id_bloque) === FALSE) {
			$bloques = "and hc.id_dia = {$id_dia} and hc.id_bloque = {$id_bloque}";
		}

		$q = <<<PQR
SELECT pd.*, concat(p.nombres, " ", p.apellidos) nombre FROM `horario_curso` hc
left outer join horarios h on h.id_dia = hc.id_dia and h.id_bloque = hc.id_bloque
		and h.id_curso = hc.id_curso
join profesor_has_curso phc on phc.id_curso = hc.id_curso
join profesores_disponibles pd on pd.id_dia = hc.id_dia and pd.id_bloque = hc.id_bloque
		and pd.id_profesor = phc.id_profesor
left join profesor p on p.id = pd.id_profesor
where h.id is null and hc.id_curso = {$id_curso}
	{$bloques}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			if (is_null($row->id_profesor) == FALSE) {
				$result[] = $row;
			}
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna bloques disponibles del curso
	 *
	 * @param int $id_curso
	 * @return array
	 * @todo Evaluar cambiar el uso de la tabla por horario_curso
	 */
	public function bloques_disponibles($id_curso)
	{
		$result = array();

		$q = <<<PQR
SELECT hc.id_dia, hc.id_bloque FROM `horario_curso` hc
left outer join horarios h on h.id_dia = hc.id_dia and h.id_bloque = hc.id_bloque and h.id_curso = hc.id_curso
where h.id is null
and hc.id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[] = $row;
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna las asignaturas y la cantidad de horas utilizadas del horario del curso.
	 * Utilizado para restar horas al plan de estudio preparado.
	 *
	 * @param integer $id_curso
	 *
	 * @return array
	 */
	public function horas_horario($id_curso)
	{
		$result = array();

		$q = <<<PQR
select id_asignatura, count(id_asignatura) horas
from horarios
where id_curso = {$id_curso}
group by id_asignatura
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $row) {
			$result[$row->id_asignatura] = $row->horas;
		}

		$r->free_result();

		return $result;
	}

}
