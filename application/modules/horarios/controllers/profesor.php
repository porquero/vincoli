<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Mantenedor de profesores
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class profesor extends MX_Controller {

	/**
	 * Listado de profesores
	 */
	public function listado()
	{
		$this->load->model('m_profesor');
		$this->load->model('m_disponibilidad_bloques');
		$this->load->model('m_curso');
		$this->load->model('m_asignatura');
		$this->load->model('m_bloque');
		$this->load->model('m_dia');
		$this->load->helper('form');

		$this->tpl->variables(
			array(
					'title' => 'Profesores',
					'head' => link_tag('pub/' . _TEMPLATE_NAME . '/css/profesor.css'),
					'listado' => $this->m_profesor->fetch(),
					'cursos' => $this->m_curso->fetch(),
					'asignaturas' => $this->m_asignatura->fetch(),
					'bloques' => $this->m_bloque->fetch(),
					'dias' => $this->m_dia->fetch(),
					'footer' => js_tag('pub/' . _TEMPLATE_NAME . '/js/crud_grid.js') .
					js_tag('pub/' . _TEMPLATE_NAME . '/js/profesor.js'),
					'm_profesor' => $this->m_profesor,
		));

		$this->tpl->section('_view', 'listado.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Interpreta formulario de listado
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
				$this->session->set_flashdata('msg', 'Registro agregado.');
				break;

			case 'Modificar':
				$resultado = $this->_modificar();
				$this->session->set_flashdata('msg', 'Registro modificado.');
				break;

			case 'Eliminar':
				$resultado = $this->_eliminar();
				$this->session->set_flashdata('msg', 'Registro eliminado.');
				break;
		}

		if ($resultado === FALSE) {
			$this->session->set_flashdata('msg_tipo', 'msg_error');
		}

		redirect('horarios/profesor/listado');
	}

	/**
	 * Intenta agregar un profesor
	 *
	 * @return boolean
	 */
	private function _agregar()
	{
		$this->load->model('m_profesor');
		$this->load->model('m_disponibilidad_bloques');

		$data = array(
				'nombres' => $this->input->post('nombres'),
				'apellidos' => $this->input->post('apellidos'),
				'horas_contrato' => $this->input->post('horas_contrato'),
				'horas_aula' => $this->input->post('horas_aula'),
				'horas_no_lectivas' => $this->input->post('horas_no_lectivas'),
				'horas_permanencia' => $this->input->post('horas_permanencia'),
				'trabajo_tecnico' => $this->input->post('trabajo_tecnico'),
				'comentario' => $this->input->post('comentario')
		);

		$id_profesor = $this->m_profesor->create($data);
		$disponibilidad = $this->_disponibilidad($id_profesor);
		$disponibilidad_especialidad = $this->_disponibilidad_especialidad($id_profesor);

		return $id_profesor !== 0 && $disponibilidad && $disponibilidad_especialidad;
	}

	/**
	 * Intenta modificar un profesor
	 *
	 * @return boolean
	 */
	private function _modificar()
	{
		$this->load->model('m_profesor');

		// Datos del profesor.
		$data_profesor = array(
				'nombres' => $this->input->post('nombres'),
				'apellidos' => $this->input->post('apellidos'),
				'horas_contrato' => $this->input->post('horas_contrato'),
				'horas_aula' => $this->input->post('horas_aula'),
				'horas_no_lectivas' => $this->input->post('horas_no_lectivas'),
				'horas_permanencia' => $this->input->post('horas_permanencia'),
				'trabajo_tecnico' => $this->input->post('trabajo_tecnico'),
				'comentario' => $this->input->post('comentario')
		);

		$update = $this->m_profesor->update($this->input->post('id'), $data_profesor);
		$disponibilidad = $this->_disponibilidad();
		$disponibilidad_especialidad = $this->_disponibilidad_especialidad();

		return $update && $disponibilidad && $disponibilidad_especialidad;
	}

	/**
	 * Intenta eliminar un profesor
	 *
	 * @return boolean
	 */
	private function _eliminar()
	{
		$this->load->model('m_profesor');

		return $this->m_profesor->delete($this->input->post('id'));
	}

	/**
	 * Actualiza la disponibilidad del profesor según los datos enviados por form.
	 * @return boolean
	 */
	private function _disponibilidad($id_profesor = FALSE)
	{
		$this->load->model('m_disponibilidad_bloques');

		$id_profesor = $id_profesor === FALSE ? $this->input->post('id') : $id_profesor;

		$data_disponibilidad = array();

		$dias = array('lu', 'ma', 'mi', 'ju', 'vi');
		foreach ($dias as $dia) {
			if (is_array($this->input->post($dia))) {
				foreach ((array) $this->input->post($dia) as $dia_bloque) {
					$data_disponibilidad[] = array(
							'id_profesor' => $id_profesor,
							'id_dia' => $dia_bloque[0],
							'id_bloque' => $dia_bloque[2]
					);
				}
			}
		}

		return $this->m_disponibilidad_bloques->create_batch($this->input->post('id'), $data_disponibilidad);
	}

	/**
	 * Actualiza la especialidad del profesor.
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	private function _disponibilidad_especialidad($id_profesor = FALSE)
	{
		$this->load->model('m_disponibilidad_especialidad');

		$id_profesor = $id_profesor === FALSE ? $this->input->post('id') : $id_profesor;

		// Cursos del profesor.
		$data_profesor_has_curso = array();
		if (is_array($this->input->post('id_curso'))) {
			$i = 0;
			foreach ((array) $this->input->post('id_curso') as $id_curso) {
				$data_profesor_has_curso[] = array(
						'id_profesor' => $id_profesor,
						'id_curso' => $id_curso,
				);
				$i ++;
			}
		}

		// Asignaturas del profesor.
		$data_profesor_has_asignatura = array();
		if (is_array($this->input->post('id_asignatura'))) {
			$i = 0;
			foreach ((array) $this->input->post('id_asignatura') as $id_asignatura) {
				$data_profesor_has_asignatura[] = array(
						'id_profesor' => $id_profesor,
						'id_asignatura' => $id_asignatura
				);
				$i ++;
			}
		}

		return $this->m_disponibilidad_especialidad->create_batch($this->input->post('id')
				, $data_profesor_has_curso, $data_profesor_has_asignatura);
	}

	/**
	 * Obtiene la información del profesor por su id.
	 *
	 * @param type $id_profesor
	 * @return type
	 */
	public function info($id_profesor)
	{
		$result = array();

		$result['disponibilidad'] = \Modules::run('horarios/disponibilidad_bloques/por_id', $id_profesor);
		$result['disponibilidad_especialidad'] = \Modules::run('horarios/disponibilidad_especialidad/por_id', $id_profesor);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

	/**
	 * Muestra la disponibilidad informada del profesor
	 *
	 * @param type $id_profesor
	 */
	public function mapa_disponibilidad($id_profesor)
	{
		$this->load->model('m_profesor');

		$this->tpl->variables(array(
				'title' => 'Disponibilidad informada por el profesor',
				'bloques_disponibles' => $this->m_profesor->mapa_disponibilidad($id_profesor),
				'head' => link_tag('css/horario.css'),
				// @TODO: Estos datos deben sacarse de la bd.
				'nombre_dias' => array('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
				'numero_bloques' => range(1, 9),
				'info' => $this->m_profesor->info($id_profesor),
		));

		$this->tpl->section('_view', 'mapa_disponibilidad.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Muestra la disponibilidad actual del profesor.
	 *
	 * @param int $id_profesor
	 */
	public function mapa_disponibilidad_actual($id_profesor)
	{
		$this->load->model('m_profesor');

		$this->tpl->variables(array(
				'bloques_disponibles' => $this->m_profesor->mapa_disponibilidad_actual($id_profesor),
				'head' => link_tag('css/horario.css'),
				// @TODO: Estos datos deben sacarse de la bd.
				'nombre_dias' => array('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
				'numero_bloques' => range(1, 9),
				'info' => $this->m_profesor->info($id_profesor),
		));

		$this->tpl->section('_view', 'mapa_disponibilidad.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Muestra el horario del profesor.
	 *
	 * @param int $id_profesor
	 */
	public function horario($id_profesor)
	{
		$this->load->model('m_profesor');

		$this->tpl->variables(array(
				'title' => 'Horario del profesor',
				'bloques_disponibles' => $this->m_profesor->horario($id_profesor),
				'head' => link_tag('css/horario.css'),
				// @TODO: Estos datos deben sacarse de la bd.
				'nombre_dias' => array('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
				'numero_bloques' => range(1, 9),
				'info' => $this->m_profesor->info($id_profesor),
		));

		$this->tpl->section('_view', 'mapa_disponibilidad.phtml');
		$this->tpl->load_view('BLANK_html.phtml');
	}

	public function disponibilidad($id_profesor)
	{
		$this->load->model('m_profesor_disponibilidad');

		var_dump($this->m_profesor_disponibilidad->por_id($id_profesor));
	}

}
