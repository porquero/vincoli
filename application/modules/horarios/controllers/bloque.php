<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Mantenedor de bloques
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class bloque extends MX_Controller {

	/**
	 * Listado de bloques
	 */
	public function listado()
	{
		$this->load->model('m_bloque');
		$this->load->helper('form');

		$this->tpl->variables(
			array(
					'title' => 'Cursos',
					'listado' => $this->m_bloque->fetch(),
					'footer' => js_tag('pub/' . _TEMPLATE_NAME . '/js/crud_grid.js') .
					js_tag('pub/' . _TEMPLATE_NAME . '/js/bloque.js'),
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

		switch ($this->input->post('submit')) {
			case 'Agregar':
				$this->_agregar();
				break;

			case 'Modificar':
				$this->_modificar();
				break;

			case 'Eliminar':
				$this->_eliminar();
				break;

			default:
				break;
		}
		redirect('horarios/bloque/listado');
	}

	/**
	 * Intenta agregar un bloque
	 *
	 * @return boolean
	 */
	private function _agregar()
	{
		$this->load->model('m_bloque');

		$data = array(
				'rango' => $this->input->post('rango'),
				'comentario' => $this->input->post('comentario')
		);

		return $this->m_bloque->create($data);
	}

	/**
	 * Intenta modificar un bloque
	 *
	 * @return boolean
	 */
	private function _modificar()
	{
		$this->load->model('m_bloque');

		$data = array(
				'rango' => $this->input->post('rango'),
				'comentario' => $this->input->post('comentario')
		);

		return $this->m_bloque->update($this->input->post('id'), $data);
	}

	/**
	 * Intenta eliminar un bloque
	 *
	 * @return boolean
	 */
	private function _eliminar()
	{
		$this->load->model('m_bloque');

		return $this->m_bloque->delete($this->input->post('id'));
	}

}
