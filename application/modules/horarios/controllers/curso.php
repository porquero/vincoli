<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Mantenedor de cursos.
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class curso extends MX_Controller {

	/**
	 * Listado de cursos
	 */
	public function listado()
	{
		$this->load->model('m_curso');
		$this->load->model('m_profesor');
		$this->load->model('m_asignatura');
		$this->load->model('m_estadisticas');
		$this->load->model('m_dia');
		$this->load->model('m_bloque');
		$this->load->helper('form');

		$this->tpl->variables(
			array(
					'title' => 'Cursos',
					'head' => link_tag('pub/' . _TEMPLATE_NAME . '/css/curso.css'),
					'listado' => $this->m_curso->fetch(),
					'profesores' => $this->m_profesor->dropdown_options(),
					'asignaturas' => $this->m_asignatura->dropdown_options(),
					'bloques' => $this->m_bloque->fetch(),
					// @TODO: Estos datos deben sacarse de la bd.
					'nombre_dias' => array('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
					'numero_bloques' => range(1, 12),
					'dias' => $this->m_dia->fetch(),
					'footer' => js_tag('pub/' . _TEMPLATE_NAME . '/js/crud_grid.js') .
					js_tag('pub/' . _TEMPLATE_NAME . '/js/curso.js'),
		));

		$this->tpl->section('_view', 'listado.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Editor manual de horario.
	 *
	 * @param type $id_curso
	 */
	public function editar_horario($id_curso)
	{
		$this->load->model('m_curso');

		$this->tpl->variables(Modules::run('horarios/horario/datos_vista_curso', $id_curso));
		$detalle_curso = $this->m_curso->info($id_curso);

		$horas_horario = $this->m_curso->horas_horario($id_curso);
		$plan_estudio = Modules::run('horarios/plan_estudio/por_id', $id_curso);

		foreach ($plan_estudio as $k => $asignatura) {
			if (array_key_exists($asignatura->id_asignatura, $horas_horario)) {
				$d = $plan_estudio[$k]->horas - $horas_horario[$asignatura->id_asignatura];
				if ($d === 0) {
					unset($plan_estudio[$k]);
				}
				else {
					$plan_estudio[$k]->horas = $d;
				}
			}
		}

		$this->tpl->variables(
			array(
				// @TODO: Estos datos deben sacarse de la bd.
				'nombre_dias' => array(1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes'),
				'numero_bloques' => range(1, 12),
				'head' => link_tag('css/curso_editar_horario.css'),
				'footer' => js_tag('js/curso_editar_horario.js'),
				'id_curso' => $id_curso,
				'plan_estudio' => $plan_estudio,
			), NULL, TRUE);

		$this->tpl->variables(array(
				'title' => 'Editar horario: ' . $detalle_curso->glosa,
		));

		$this->tpl->section('_view', 'editar_horario.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Interpreta formuario del listado
	 *
	 * @return void
	 */
	public function crud()
	{
		$this->load->helper('url');
		$resultado = false;
		$this->session->set_flashdata('msg', 'Ha ocurrido un error. Favor Comunicar.');
		$this->session->set_flashdata('msg_tipo', 'msg_ok');

		switch ($this->input->post('submit')) {
			case 'Agregar':
				$resultado = $this->_agregar();
				$this->session->set_flashdata('msg', 'Curso agregado.');
				break;

			case 'Modificar':
				$resultado = $this->_modificar();
				$this->session->set_flashdata('msg', 'Curso modificado.');
				break;

			case 'Eliminar':
				$resultado = $this->_eliminar();
				$this->session->set_flashdata('msg', 'Curso eliminado.');
				break;
		}

		if ($resultado === FALSE) {
			$this->session->set_flashdata('msg_tipo', 'msg_error');
		}

		redirect('horarios/curso/listado');
	}

	/**
	 * Intenta agregar un curso
	 *
	 * @return boolean
	 */
	private function _agregar()
	{
		$this->load->model('m_curso');

		$id_profesor = $this->input->post('id_profesor');
		$data = array(
				'glosa' => $this->input->post('glosa'),
				'id_profesor' => $id_profesor == '' ? null : $id_profesor,
				'comentario' => $this->input->post('comentario')
		);

		$id_curso = $this->m_curso->create($data);
		$plan_estudios = $this->_plan_estudio($id_curso);
		$horario_curso = $this->_horario($id_curso);

		return $id_curso !== 0 && $plan_estudios && $horario_curso;
	}

	/**
	 * Intenta modificar un curso
	 *
	 * @return boolean
	 */
	private function _modificar()
	{
		$this->load->model('m_curso');
		$this->load->model('m_transaction');

		$id_profesor = $this->input->post('id_profesor');
		$data = array(
				'glosa' => $this->input->post('glosa'),
				'id_profesor' => $id_profesor == '' ? null : $id_profesor,
				'comentario' => $this->input->post('comentario')
		);

		$update = $this->m_curso->update($this->input->post('id'), $data);
		$plan_estudios = $this->_plan_estudio();
		$horario_curso = $this->_horario();

		return $update && $plan_estudios && $horario_curso;
	}

	/**
	 * Intenta eliminar un curso
	 *
	 * @return boolean
	 */
	private function _eliminar()
	{
		$this->load->model('m_curso');

		return $this->m_curso->delete($this->input->post('id'));
	}

	/**
	 * Obtiene la información del curso por su id.
	 *
	 * @param type $id_curso
	 * @return type
	 */
	public function info($id_curso)
	{
		$result = array();

		$result['plan_estudio'] = Modules::run('horarios/plan_estudio/por_id', $id_curso);
		$result['horario'] = Modules::run('horarios/curso/horario_bloques', $id_curso);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

	/**
	 * Actualiza el plan de estudio del curso según los datos enviados por form.
	 *
	 * @param bool $id_curso Identificador del curso.
	 *
	 * @return boolean
	 */
	private function _plan_estudio($id_curso = FALSE)
	{
		$this->load->model('m_plan_estudio');

		$id_curso = $id_curso === FALSE ? $this->input->post('id') : $id_curso;

		$data_plan_estudio = array();
		if (is_array($this->input->post('id_asignatura'))) {
			// Se usa asi en vez de next() porque no funciona para este caso!
			$i = 0;

			foreach ((array) $this->input->post('id_asignatura') as $id_asignatura) {
				$horas = $this->input->post('horas');
				$id_profesor_asignatura = $this->input->post('id_profesor_asignatura');

				$data_plan_estudio[] = array(
						'id_curso' => $id_curso,
						'id_asignatura' => $id_asignatura,
						'id_profesor' => $id_profesor_asignatura [$i] === '' ? null : $id_profesor_asignatura[$i],
						'horas' => $horas[$i],
				);
				$i ++;
			}

			return $this->m_plan_estudio->create_batch($this->input->post('id'), $data_plan_estudio);
		}

		return false;
	}

	/**
	 * Actualiza el horario del curso.
	 *
	 * @return boolean
	 */
	private function _horario($id_curso = FALSE)
	{
		$this->load->model('m_horario_curso');

		$id_curso = $id_curso === FALSE ? $this->input->post('id') : $id_curso;

		$data_disponibilidad = array();

		$dias = array('lu', 'ma', 'mi', 'ju', 'vi');
		foreach ($dias as $dia) {
			if (is_array($this->input->post($dia))) {
				foreach ((array) $this->input->post($dia) as $dia_bloque) {
					$diaBloque = explode('_', $dia_bloque);
					$data_disponibilidad[] = array(
							'id_curso' => $id_curso,
							'id_dia' => $diaBloque[0],
							'id_bloque' => $diaBloque[1]
					);
				}
			}
		}

		return $this->m_horario_curso->create_batch($this->input->post('id'), $data_disponibilidad);
	}

	/**
	 * Obtiene bloques del horario del curso
	 *
	 * @param int $id_curso
	 * @return array/json
	 */
	public function horario_bloques($id_curso)
	{
		$this->load->model('m_horario_curso');

		$result = $this->m_horario_curso->horario_bloques($id_curso);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

	public function bloques_disponibles($id_curso)
	{
		$this->load->model('m_curso');
		//Plogger::var_dump($this->m_curso->bloques_disponibles($id_curso));
	}

	/**
	 * Retorna los bloques libres del curso con los profesores que pueden fijarse en éstos.
	 *
	 * @param integer $id_curso
	 *
	 * @return array
	 */
	public function bloques_con_profesores_disponibles($id_curso, $id_dia = NULL, $id_bloque = NULL)
	{
		$this->load->helper('form');
		$this->load->model('m_curso');
		$this->load->model('m_profesor');
		$profesores = array();
		$bloques = $this->m_curso->bloques_con_profesores_disponibles($id_curso, $id_dia, $id_bloque);


		if ($this->input->is_ajax_request()) {
			foreach ($bloques as $profesor) {
				$profesores[$profesor->id_profesor] = $profesor->nombre;
			}

			$profesores_select = form_dropdown('profesores', $profesores);

			echo $profesores_select;
		}
		else {
			foreach ($bloques as $profesor) {
				$profesores[] = array(
						'profesor' => $profesor,
				);
			}

			return $profesores;
		}
	}

	/**
	 * Retorna select con profesores para la asignatura del curso.
	 *
	 * @param type $id_curso
	 * @param type $id_asignatura
	 *
	 * @return type
	 */
	public function profesores_para_asignatura($id_curso, $id_asignatura, $dropdown = TRUE)
	{
		$this->load->helper('form');
		$this->load->model('m_profesor');

		$profesores = $this->m_profesor->para_curso_asignatura($id_curso, $id_asignatura);

		if ($dropdown === TRUE) {
			$result = form_dropdown('id_profesor_asignatura[]', array('' => '-- Sin asignar --') + $profesores);
		}
		else {
			return $profesores;
		}

		if ($this->input->is_ajax_request()) {
			echo $result;
		}
		else {
			return $result;
		}
	}

	/**
	 * Retorna asignaturas y profesores disponibles para el bloque del día y el curso enviado.
	 *
	 * @param int $id_curso
	 * @param int $id_dia
	 * @param int $id_bloque
	 *
	 * @return array
	 */
	public function asignaturas_para_bloque($id_curso, $id_dia, $id_bloque)
	{
		$this->load->helper('form');
		$this->load->model('m_plan_estudio');
		$this->load->model('m_horario');
		$this->load->model('m_asignatura');
		$this->load->model('m_profesor');

		$profesores_disponibles = array();
		$asignaturas = array();

		$asignaturas_faltantes = $this->m_plan_estudio->asignaturas_faltantes($id_curso);

		foreach ($asignaturas_faltantes as $asignatura) {
			$profesores_disponibles[$asignatura->id_asignatura] = $this->m_horario->profesores_disponibles_para_asignatura($id_curso
				, $asignatura->id_asignatura, $asignatura->horas_faltantes, FALSE);
		}

		foreach ($profesores_disponibles as $id_asignatura => $profesores) {
			foreach ($profesores as $profesor) {
				if ($profesor['id_dia'] === $id_dia && $profesor['id_bloque'] === $id_bloque) {
					$asignatura_disponible = $this->m_asignatura->info($id_asignatura);
					$profesor_disponible = $this->m_profesor->info($profesor['id_profesor']);
					$asignaturas["{$id_asignatura}_{$profesor['id_profesor']}"] = $asignatura_disponible->glosa . ' - '
						. $profesor_disponible->nombres . ' ' . $profesor_disponible->apellidos;
				}
			}
		}

		$asignaturas = form_dropdown('asignatura', array('' => '-- Asignatura --') + $asignaturas, array(), 'class="asignatura"');

		if ($this->input->is_ajax_request()) {
			echo $asignaturas;
		}
		else {
			return $asignaturas;
		}
	}

	/**
	 * Libera un bloque del horario del curso
	 *
	 * @param integer $id_curso
	 * @param integer $id_dia
	 * @param integer $id_bloque
	 *
	 * @return boolean
	 */
	public function liberar_bloque($id_curso, $id_dia, $id_bloque)
	{
		$this->load->model('m_horario');

		return $this->m_horario->vaciar_bloque($id_curso, $id_dia, $id_bloque);
	}

	/**
	 * Establece una asignatura para un bloque del curso.
	 *
	 * @param integer $id_curso
	 * @param integer $id_dia
	 * @param integer $id_bloque
	 * @param integer $id_asignatura
	 * @param integer $id_profesor
	 * @param string $asignatura Glosa de la asignatura
	 * @param string $profesor Nombre del profesor
	 *
	 * @return string Html para llenar el bloque en la vista.
	 */
	public function establecer_asignatura($id_curso, $id_dia, $id_bloque, $id_asignatura, $id_profesor, $asignatura, $profesor)
	{
		$this->load->model('m_horario');

		$datos = array(
				'id_curso' => $id_curso,
				'id_bloque' => $id_bloque,
				'id_dia' => $id_dia,
				'id_asignatura' => $id_asignatura,
				'id_profesor' => $id_profesor,
				'fijado_manualmente' => '1',
		);

		$this->m_horario->llenar_bloque($datos);

		$asignatura = urldecode($asignatura);
		$profesor = urldecode($profesor);
		$horario_profesor = site_url('horarios/profesor/horario/' . $id_profesor);

		$htm_bloque = <<<PQR
<p><b>{$asignatura}</b></p>
<a href="{$horario_profesor}">{$profesor}</a>
<input type="button" value="X" class="x_plan" title="Eliminar Asignatura" />
PQR;

		if ($this->input->is_ajax_request()) {
			echo $htm_bloque;
		}
		else {
			return $htm_bloque;
		}
	}

	/**
	 * Borra todo el horario del curso
	 * 
	 * @param integer $id_curso
	 *
	 * @return void
	 */
	public function borrar_horario($id_curso)
	{
		$this->output->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0', FALSE);
		$this->output->set_header('Pragma: no-cache');

		$this->load->model('m_horario');

		$this->m_horario->borrar_del_curso($id_curso);

		redirect('horarios/curso/editar_horario/' . $id_curso);
	}

	/**
	 * Restablece el horario del curso manteniendo los bloques fijados manualmente
	 *
	 * @param integer $id_curso
	 *
	 * @return void
	 */
	public function restablecer_horario($id_curso)
	{
		$this->output->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0', FALSE);
		$this->output->set_header('Pragma: no-cache');

		$this->load->model('m_horario');

		$this->m_horario->restablecer_del_curso($id_curso);

		redirect('horarios/curso/editar_horario/' . $id_curso);
	}

	public function profesores_disponibles_asignatura($id_curso, $id_asignatura, $horas)
	{
		return $this->m_horario->profesores_disponibles_para_asignatura($id_curso, $id_asignatura, $horas);
	}

}
