<?php

/**
 * Description of m_horario
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class m_horario extends CI_Model {

	/**
	 * Carga la base de datos
	 */
	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('', TRUE);
	}

	/**
	 * Retorna las asignaturas del curso según su plan de estudio.
	 *
	 * @param int $id_curso
	 * @return array
	 *
	 * @deprecated since version 0.12 Usar m_plan_estudio->del_curso()
	 */
	public function asignaturas_del_curso($id_curso)
	{
		$result = array();

		$q = $this->db->select('a.id, a.glosa, pe.horas')
			->from('plan_estudio pe')
			->join('asignatura a', 'pe.id_asignatura=a.id', 'left')
			->order_by('pe.horas', 'desc')
			->where('pe.id_curso', $id_curso)
			->get();

		foreach ($q->result() as $row) {
			$result[] = $row;
		}

		$q->free_result();

		return $result;
	}

	/**
	 * Retorna los bloques disponibles del profesor para el curso y la cantidad de horas solicitadas.
	 *
	 * @param int $id_curso
	 * @param array $profesores_disponibles
	 * @param int $id_curso Para evitar colisión de bloques.
	 * @param int $horas
	 * @param bool $bloques_contiguos Indica si se deben buscar sólo vloques contiguos
	 * @return array
	 */
	public function bloques_disponibles_profesor($id_curso, $profesores_disponibles, $horas, $bloques_contiguos
	, $id_asignatura)
	{
		foreach ($profesores_disponibles as $profesor) {

			$result = array();

			if ($horas == 0) {
				return $result;
			}

			$q = <<<PQR
select pd.* from 
profesores_disponibles pd
left outer join (
    select id, id_dia, id_bloque
	from horarios
	where id_curso = {$id_curso}) hc
on pd.id_dia = hc.id_dia and pd.id_bloque = hc.id_bloque
join horario_curso hcu on hcu.id_dia = pd.id_dia and hcu.id_bloque = pd.id_bloque and hcu.id_curso = {$id_curso}
where hc.id is null
group by id_profesor, id_dia, id_bloque with rollup
having id_profesor = {$profesor['id_profesor']}
PQR;
			$r = $this->db->query($q);

			$id_bloque = NULL;
			$horas_max = 0;
			foreach ($r->result() as $disponibilidad) {

				// Si se encuentra un bloque nulo (with rollup) se reinicia la recopilación.
				if (is_null($disponibilidad->id_bloque) == TRUE && $bloques_contiguos === TRUE) {
					$id_bloque = NULL;
					$horas_max = 0;
					$result = array();
					continue;
				}
				elseif (is_null($disponibilidad->id_bloque) == TRUE && $bloques_contiguos === FALSE) {
					continue;
				}

				// Evita que se repita una asignatura en el mismo día.
				if ($this->limitar_en_dia($id_curso, $id_asignatura, $disponibilidad->id_dia) === TRUE OR $this->limitar_en_bloque($id_curso, $id_asignatura, $disponibilidad->id_bloque) === TRUE) {
					$id_bloque = NULL;
					$horas_max = 0;
					$result = array();
					continue;
				}

				$result[] = $disponibilidad;

				// Si se solicitan más de una hora se valida que los bloques sean contiguos. Se otra forma se limpia lo recopilado.
				if (is_null($id_bloque) == FALSE && ($disponibilidad->id_bloque == $id_bloque + 1) == FALSE && $bloques_contiguos === TRUE) {
					$id_bloque = NULL;
					$horas_max = 0;
					$result = array();
					continue;
				}

				$id_bloque = $disponibilidad->id_bloque;
				$horas_max ++;

				// Se valida la cantidad de ciclos para no realizar más que las horas solicitadas.
				if ($horas_max == $horas) {
					return $result;
				}
			}

			if (count($result) > 0) {
				return $result;
			}
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna el primer profesor disponible para el curso, la asignatura y las horas necesarias. Si no hay un profesor
	 * disponible, a la asignatura la relaciona con el número 0. Esto es para saber qué asignatura no encontró
	 * profesor.
	 * Utilizado para saber si hay un profesor disponible para el total de horas de la asignatura que corresponde
	 * al plan de estudio del curso.
	 *
	 * @param int $id_curso
	 * @param int $id_asignatura
	 * @param int $horas
	 * @return array
	 *
	 * @deprecated Ahora se trabaja con todos los profesores disponibles (profesores_disponibles_para_asignatura)
	 */
	public function profesor_disponible_para_asignatura($id_curso, $id_asignatura, $horas)
	{
		if ($horas == 0) {
			return array();
		}

		$q = <<<PQR
SELECT db.*, count(db.id_dia) horas
FROM (`profesores_disponibles` db)
LEFT JOIN `profesor_has_asignatura` pha ON `pha`.`id_profesor` = `db`.`id_profesor`
left join profesor_has_curso phc on phc.id_profesor = db.id_profesor
WHERE `pha`.`id_asignatura` =  {$id_asignatura}
and phc.id_curso = {$id_curso}
GROUP BY `db`.`id_profesor`
HAVING count(db.id_profesor) >= {$horas}
PQR;

		$profesor = $this->db->query($q)->row();

		if (is_array($profesor) === TRUE) {
			return array();
		}

		return array(
				'id_profesor' => $profesor->id_profesor,
				'horas' => $profesor->horas,
				'id_asignatura' => $id_asignatura,
				'id_dia' => $profesor->id_dia,
				'id_bloque' => $profesor->id_bloque,
		);
	}

	/**
	 * Retorna los profesores disponibles para el curso, la asignatura y las horas necesarias.
	 *
	 * @param int $id_curso
	 * @param int $id_asignatura
	 * @param int $horas
	 * @param bool $validar_dia Flag para indicar si se valida que la asignatura no se repita demasiadas veces en el mismo día.
	 *
	 * @return array
	 */
	public function profesores_disponibles_para_asignatura($id_curso, $id_asignatura, $horas, $validar_dia = TRUE)
	{
		$result = array();

		if ($horas == 0) {
			return array();
		}

		// Consulta si la asignatura tiene un profesor asignado.
		$and_profesor_asignado = '';
		$q = <<<PQR
select id_profesor
from plan_estudio pe
where id_asignatura = {$id_asignatura} and id_curso = {$id_curso}
PQR;
		$profesor_asignado = $this->db->query($q)->row();
		if ($profesor_asignado->id_profesor > 0) {
			$and_profesor_asignado = 'and phc.id_profesor = ' . $profesor_asignado->id_profesor;
		}

		$q = <<<PQR
SELECT pd.*
FROM (`profesores_disponibles` pd)
LEFT JOIN `profesor_has_asignatura` pha ON `pha`.`id_profesor` = `pd`.`id_profesor`
left join profesor_has_curso phc on phc.id_profesor = pd.id_profesor
join horario_curso hc on hc.id_dia = pd.id_dia and hc.id_bloque = pd.id_bloque and hc.id_curso = {$id_curso}
WHERE `pha`.`id_asignatura` =  {$id_asignatura}
and phc.id_curso = {$id_curso}
{$and_profesor_asignado}
PQR;

		$r = $this->db->query($q);

		foreach ($r->result() as $profesor) {
			// Evita que se repita una asignatura en el mismo día.
			if (($this->limitar_en_dia($id_curso, $id_asignatura, $profesor->id_dia) === TRUE OR $this->limitar_en_bloque($id_curso, $id_asignatura, $profesor->id_bloque) === TRUE) && ($validar_dia === TRUE)) {
				continue;
			}

			$result [] = array(
					'id_profesor' => $profesor->id_profesor,
					'id_asignatura' => $id_asignatura,
					'id_dia' => $profesor->id_dia,
					'id_bloque' => $profesor->id_bloque,
			);
		}

		$r->free_result();

		return $result;
	}

	/**
	 * Retorna los profesores disponibles para el plan de estudio enviado.
	 * 
	 * @param array $plan_estudio plan de estudio de un curso.
	 * @param type $id_curso
	 * @param m_plan_estudio $m_plan_estudio
	 * @return array
	 */
	public function profesores_disponibles_para_plan_estudio($plan_estudio, $id_curso, m_plan_estudio $m_plan_estudio)
	{
		$this->load->helper('vincoli');
		$profesores = array();

		// Valida si la asignatura ya está en el horario del curso.
		foreach ($plan_estudio as $asignatura) {
			if ($this->total_horas_asignatura($id_curso, $asignatura['id_asignatura']) === $m_plan_estudio->total_horas_asignatura($id_curso, $asignatura['id_asignatura'])) {
				continue;
			}

			$profesores_disponibles = $this->profesores_disponibles_para_asignatura($id_curso, $asignatura['id_asignatura'], $asignatura['horas']);

			if (count($profesores_disponibles) === 0) {
				continue;
			}

			foreach ($profesores_disponibles as $profesor_disponible) {
				$profesores[$asignatura['id_asignatura']][] = $profesor_disponible;
			}
		}

		// Esto no sería necesario ya que ahora obtenemos todos los profesores disponibles. @todo Revisar
		// Se ordenan profesores por cantidad de horas disponibles de menor a mayor para darle prioridad a los primeros.
//        uasort($profesores, 'ordernar_por_hora');
//        foreach ($profesores as $id_asignatura => $profesor) {
//            if (count($profesor) === 0) {
//                continue;
//            }
//
//            $profesores[$id_asignatura] = $profesor['id_profesor'];
//        }

		return $profesores;
	}

	/**
	 * Agrega asignatura al horario con los datos curso, día, bloque y profesor.
	 * 
	 * @param array $datos
	 * @return bool
	 */
	public function agregar_asignatura($datos)
	{
		return $this->db->insert_batch('horarios', $datos);
	}

	/**
	 * Retorna el horario del curso.
	 * 
	 * @param int $id_curso
	 * @return array
	 */
	public function del_curso($id_curso)
	{
		$result = array();
		$q = $this->db->select('h.*, a.glosa asignatura, concat(p.nombres, \' \',  p.apellidos) profesor,'
				. 'd.glosa dia', FALSE)
			->from('horarios h')
			->join('asignatura a', 'h.id_asignatura=a.id')
			->join('profesor p', 'h.id_profesor=p.id')
			->join('dia d', 'h.id_dia=d.id')
			->where('id_curso', $id_curso)
			->order_by('id_dia')
			->order_by('id_bloque')
			->get();

		foreach ($q->result() as $bloque) {
			$result[$bloque->dia][$bloque->id_bloque] = array(
					'id_asignatura' => $bloque->id_asignatura,
					'asignatura' => $bloque->asignatura,
					'id_profesor' => $bloque->id_profesor,
					'profesor' => $bloque->profesor,
					'fijado_manualmente' => $bloque->fijado_manualmente,
			);
		}

		$q->free_result();

		return $result;
	}

	/**
	 * Vacia la tabla horarios.
	 *
	 * @return bool
	 */
	public function vaciar()
	{
		return $this->db->truncate('horarios');
	}

	/**
	 * Elimina horario del curso
	 *
	 * @param int $id_curso
	 * @return bool
	 */
	public function borrar_del_curso($id_curso)
	{
		$q = 'delete from horarios where id_curso = ' . $id_curso;

		return $this->db->query($q);
	}

	/**
	 * Restablece el horario del curso
	 *
	 * @param int $id_curso
	 * @return bool
	 */
	public function restablecer_del_curso($id_curso)
	{
		$q = 'delete from horarios where id_curso = ' . $id_curso . ' and fijado_manualmente = \'0\'';

		return $this->db->query($q);
	}

	/**
	 * Restablece todos los horarios
	 *
	 * @param int $id_curso
	 * @return bool
	 */
	public function restablecer()
	{
		$q = 'delete from horarios where fijado_manualmente = \'0\'';

		return $this->db->query($q);
	}

	/**
	 * Retorna el total de horas que tiene el horario del curso.
	 *
	 * @param int $id_curso
	 * @return int
	 */
	public function total_horas($id_curso)
	{
		$q = <<<PQR
SELECT COUNT( * ) horas
FROM  `horarios`
WHERE id_curso = {$id_curso}
PQR;

		$q = $this->db->query($q);
		$r = $q->row();

		return $r->horas;
	}

	/**
	 * Retorna el total de horas que tiene el horario del curso para la asignatura.
	 *
	 * @param int $id_curso
	 * @return int
	 */
	public function total_horas_asignatura($id_curso, $id_asignatura)
	{
		$q = <<<PQR
SELECT COUNT( * ) horas
FROM  `horarios`
WHERE id_curso = {$id_curso}
AND id_asignatura = {$id_asignatura}
PQR;

		$q = $this->db->query($q);
		$r = $q->row();

		return $r->horas;
	}

	/**
	 * Retorna información del horario para el día y el bloque enviado.
	 * 
	 * @param int $id_dia
	 * @param int $id_bloque
	 * @return obj|array
	 */
	public function info_bloque($id_curso, $id_dia, $id_bloque)
	{
		$q = $this->db->select()
			->from('horarios')
			->where('id_dia', $id_dia)
			->where('id_bloque', $id_bloque)
			->where('id_curso', $id_curso)
			->get();

		return $q->row();
	}

	/**
	 * Elimina la asignatura de un bloque del curso
	 *
	 * @param integer $id_curso
	 * @param integer $id_dia
	 * @param integer $id_bloque
	 * 
	 * @return boolean
	 */
	public function vaciar_bloque($id_curso, $id_dia, $id_bloque)
	{
		$this->db->where('id_curso', $id_curso);
		$this->db->where('id_dia', $id_dia);
		$this->db->where('id_bloque', $id_bloque);

		return $this->db->delete('horarios');
	}

	/**
	 * Completa un bloque del curso.
	 *
	 * @param type $datos
	 * @todo Validar que no esté duplicado.
	 */
	public function llenar_bloque($datos)
	{
		$this->db->insert('horarios', $datos);
	}

	/**
	 * Retorna true si la asignatura ya está en el día más de 2 veces.
	 *
	 * @param integer $id_curso
	 * @param integer $id_asignatura
	 * @param integer $id_dia
	 *
	 * @return boolean
	 */
	public function limitar_en_dia($id_curso, $id_asignatura, $id_dia)
	{
		$q = <<<PQR
SELECT count(*) bloques
FROM `horarios` 
where id_curso = {$id_curso} and id_asignatura = {$id_asignatura} and id_dia = {$id_dia}
PQR;

		$r = $this->db->query($q)->row();

		return $r->bloques > 1;
	}

	/**
	 * Retorna true si la asignatura ya está en la línea del bloque más de 2 veces.
	 * Esto es para evitar que un ramo se repita horizontalmente en el mismo nivel de bloque, por ejemplo que
	 * no apareza en la primera hora mié, juem vie.
	 *
	 * @param integer $id_curso
	 * @param integer $id_asignatura
	 * @param integer $id_bloque
	 *
	 * @return boolean
	 */
	public function limitar_en_bloque($id_curso, $id_asignatura, $id_bloque)
	{
		$q = <<<PQR
SELECT count(*) dias
FROM `horarios`
where id_curso = {$id_curso} and id_asignatura = {$id_asignatura} and id_bloque = {$id_bloque}
PQR;

		$r = $this->db->query($q)->row();

		return $r->dias > 1;
	}

}
