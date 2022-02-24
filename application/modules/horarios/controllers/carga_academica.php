<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Controlador para la carga académica.
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class carga_academica extends MX_Controller {

	/**
	 * Obtiene carga académica de un profesor por su id.
	 *
	 * @param integer $id_profesor
	 * @return mixed json/array
	 */
	public function por_id($id_profesor)
	{
		$this->load->model('m_carga_academica');

		$result = $this->m_carga_academica->por_id($id_profesor);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

}
