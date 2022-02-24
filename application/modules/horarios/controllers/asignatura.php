<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Mantenedor de asignaturas
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class asignatura extends MX_Controller {

	/**
	 * Listado de asignaturas
	 */
	public function listado()
	{
		$this->load->model('m_asignatura');
		$this->load->helper('form');

		$this->tpl->variables(
			array(
					'title' => 'Asignaturas',
					'listado' => $this->m_asignatura->fetch(),
					'footer' => js_tag('pub/' . _TEMPLATE_NAME . '/js/crud_grid.js') .
					js_tag('pub/' . _TEMPLATE_NAME . '/js/asignatura.js'),
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
		$resultado = FALSE;
		$this->session->set_flashdata('msg', 'Ha ocurrido un error. Favor Comunicar.');
		$this->session->set_flashdata('msg_tipo', 'msg_ok');

		switch ($this->input->post('submit')) {
			case 'Agregar':
				$resultado = $this->_agregar();
				if (is_int($resultado) === TRUE) {
					$this->session->set_flashdata('msg', 'Asignatura agregada.');
				}
				break;

			case 'Modificar':
				$resultado = $this->_modificar();
				if ($resultado === TRUE) {
					$this->session->set_flashdata('msg', 'Asignatura modificada.');
				}
				break;

			case 'Eliminar':
				$resultado = $this->_eliminar();
				$this->session->set_flashdata('msg', 'Asignatura eliminada.');
				break;
		}

		if ($resultado === FALSE) {
			$this->session->set_flashdata('msg_tipo', 'msg_error');
		}

		redirect('horarios/asignatura/listado');
	}

	/**
	 * Intenta agregar una asignatura
	 *
	 * @return boolean
	 */
	private function _agregar()
	{
		$this->load->model('m_asignatura');

		if ($this->existe($this->input->post('glosa'))) {
			return FALSE;
		}


		$data = array(
				'glosa' => $this->input->post('glosa'),
		);

		return $this->m_asignatura->create($data);
	}

	/**
	 * Indica si existe una asignatura y fija un mensaje de error con dicha información.
	 *
	 * @param type $glosa
	 *
	 * @return boolean
	 */
	public function existe($glosa)
	{
		$this->load->model('m_asignatura');

		if ($this->m_asignatura->existe($glosa)) {
			$this->session->set_flashdata('msg', 'La asignatura: "' . $glosa . '" ya existe.');

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Intenta modificar una asignatura
	 *
	 * @return boolean
	 */
	private function _modificar()
	{
		$this->load->model('m_asignatura');

		if ($this->existe($this->input->post('glosa'))) {
			return FALSE;
		}

		$data = array(
				'glosa' => $this->input->post('glosa'),
		);

		return $this->m_asignatura->update($this->input->post('id'), $data);
	}

	/**
	 * Intenta eliminar una asignatura
	 *
	 * @return boolean
	 */
	private function _eliminar()
	{
		$this->load->model('m_asignatura');

		return $this->m_asignatura->delete($this->input->post('id'));
	}

}
