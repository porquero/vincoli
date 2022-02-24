<?php

/**
 * Consultas para hacer para contar horas de cursos, profesoresy colegio.
 *
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class M_estadisticas extends CI_Model {

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
	 * Retorna el total de horas de todos los profesores
	 */
	public function total_horas_profesores($id_dia = null, $id_bloque = null)
	{
		if (is_null($id_dia) === FALSE) {
			$this->db->where('id_dia', $id_dia);
		}

		if (is_null($id_bloque) === FALSE) {
			$this->db->where('id_bloque', $id_bloque);
		}

		$r = $this->db->select('count(*) total')
			->from('disponibilidad_bloques')
			->get()
			->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas de los horarios de todos los cursos.
	 * Debe ser igual que la cantidad de horas que en total_horas_planes_estudios()
	 */
	public function total_horas_horarios($id_dia = null, $id_bloque = null)
	{
		if (is_null($id_dia) === FALSE) {
			$this->db->where('id_dia', $id_dia);
		}

		if (is_null($id_bloque) === FALSE) {
			$this->db->where('id_bloque', $id_bloque);
		}

		$r = $this->db->select('count(*) total')
			->from('horario_curso')
			->get()
			->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas de los planes de estudio de todos los cursos.
	 * Debe ser igual que la cantidad de horas que en total_horas_horarios()
	 */
	public function total_horas_planes_estudios()
	{
		$q = <<<PQR
SELECT sum(horas) total FROM `plan_estudio`
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas de los profesores relacionados al curso.
	 *
	 * @param integer $id_curso
	 */
	public function total_horas_profesores_curso($id_curso)
	{
		$q = <<<PQR
SELECT count(*) total FROM `plan_estudio` pe
left join profesor_has_curso phc on phc.id_profesor = pe.id_profesor
left join disponibilidad_bloques db on db.id_profesor = phc.id_profesor
where pe.id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas del plan de estudio del curso.
	 * Debe ser igual que las horas que en total_horas_horario()
	 *
	 * @param integer $id_curso
	 */
	public function total_horas_plan_estudio($id_curso)
	{
		$q = <<<PQR
SELECT sum(horas) total FROM `plan_estudio`
where id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas del horario del curso.
	 * Debe ser igual que las horas que en total_horas_plan_estudio()
	 *
	 * @param integer $id_curso
	 */
	public function total_horas_horario($id_curso)
	{
		$q = <<<PQR
SELECT count(*) total FROM `horario_curso`
where id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna el total de horas del profesor.
	 *
	 * @param integer $id_profesor
	 */
	public function horas_profesor($id_profesor)
	{
		$q = <<<PQR
SELECT count(*) total FROM `disponibilidad_bloques`
where id_profesor = {$id_profesor}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna la cantidad de horas que faltan asignar del curso.
	 *
	 * @param integer $id_curso
	 */
	public function horas_faltantes_curso($id_curso)
	{
		$q = <<<PQR
SELECT count(*) total FROM horario_curso hc
left outer join horarios h on h.id_curso = hc.id_curso
and hc.id_dia = h.id_dia and hc.id_bloque = h.id_bloque
where hc.id_curso = {$id_curso} and h.id is null
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna la cantidad de horas disponible de profesores para el curso
	 *
	 * @param integer $id_curso
	 */
	public function horas_profesores_disponibles($id_curso)
	{
		$q = <<<PQR
SELECT count(*) total FROM `profesores_disponibles` pd
left join profesor_has_curso phc on phc.id_profesor = pd.id_profesor
join horario_curso hc on hc.id_dia = pd.id_dia and hc.id_bloque = pd.id_bloque
where phc.id_curso = {$id_curso} and hc.id_curso = {$id_curso}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Retorna la cantidad de horas disponibles de profesores del curso para la asignatura.
	 *
	 * @param integer $id_curso
	 * @param integer $id_asignatura
	 */
	public function horas_profesores_disponibles_asignatura($id_curso, $id_asignatura)
	{
		$q = <<<PQR
select count(*) total from profesores_disponibles pd
join profesor_has_curso phc on phc.id_profesor = pd.id_profesor
join profesor_has_asignatura pha on pha.id_profesor = pd.id_profesor
join horario_curso hc on hc.id_dia = pd.id_dia and hc.id_bloque = pd.id_bloque
where hc.id_curso = {$id_curso} and phc.id_curso = {$id_curso}
and id_asignatura = {$id_asignatura}
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Total de cursos
	 * 
	 * @return int
	 */
	public function total_cursos()
	{
		$q = <<<PQR
select count(*) total
from curso
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Total de profesores
	 *
	 * @return int
	 */
	public function total_profesores()
	{
		$q = <<<PQR
select count(*) total
from profesor
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Entrega el total de profesores disponibles para el día y el bloque respectivo.
	 *
	 * @param int $id_dia
	 * @param int  $id_bloque
	 *
	 * @return int
	 */
	public function total_horas_profesores_disponibles($id_dia, $id_bloque)
	{
		$q = <<<PQR
SELECT count(distinct pd.id_profesor) total FROM `profesores_disponibles`  pd
left join profesor_has_curso phc on phc.id_profesor = pd.id_profesor
left join horario_curso hc on hc.id_curso = phc.id_curso
where #hc.id_curso = 19 and
pd.id_dia = hc.id_dia
and pd.id_bloque = hc.id_bloque
and hc.id_dia = {$id_dia}
and hc.id_bloque = {$id_bloque}
#and pd.id_profesor = 66
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Entrega total de cursos que tienen bloques sin asignar profesor
	 * 
	 * @param int $id_dia
	 * @param int $id_bloque
	 * 
	 * @return int Total de cursos
	 */
	public function total_cursos_sin_horario($id_dia, $id_bloque)
	{
		$q = <<<PQR
SELECT count(distinct hc.id_curso) total FROM `horario_curso` hc
left outer join horarios h on h.id_curso = hc.id_curso
and h.id_dia = hc.id_dia and h.id_bloque = hc.id_bloque
where h.id is null
and hc.id_dia = {$id_dia}
and hc.id_bloque = {$id_bloque}
#and hc.id_curso = 30
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Entrega total de cursos que tienen bloques con asignar profesor asignado
	 *
	 * @param int $id_dia
	 * @param int $id_bloque
	 *
	 * @return int Total de cursos
	 */
	public function total_cursos_con_horario($id_dia, $id_bloque)
	{
		$q = <<<PQR
SELECT count(distinct hc.id_curso) total FROM `horario_curso` hc
left outer join horarios h on h.id_curso = hc.id_curso
and h.id_dia = hc.id_dia and h.id_bloque = hc.id_bloque
where h.id is not null
and hc.id_dia = {$id_dia}
and hc.id_bloque = {$id_bloque}
#and hc.id_curso = 30
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

	/**
	 * Entrega total de cursos cursos que están en el bloque enviado
	 *
	 * @param int $id_dia
	 * @param int $id_bloque
	 *
	 * @return int Total de cursos
	 */
	public function total_cursos_por_bloque($id_dia, $id_bloque)
	{
		$q = <<<PQR
SELECT count(distinct hc.id_curso) total FROM `horario_curso` hc
left outer join horarios h on h.id_curso = hc.id_curso
and h.id_dia = hc.id_dia and h.id_bloque = hc.id_bloque
where hc.id_dia = {$id_dia}
and hc.id_bloque = {$id_bloque}
#and hc.id_curso = 30
PQR;

		$r = $this->db->query($q)->row();

		return $r->total;
	}

}