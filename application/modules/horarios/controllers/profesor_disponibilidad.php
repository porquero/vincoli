<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 * Controlador para manejar la disponibilidad del profesor.
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class profesor_disponibilidad extends MX_Controller {

	/**
	 * Obtiene disponibilidad del profesor por id
	 *
	 * @param integer $id_profesor
	 * @return array/json
	 */
	public function por_id($id_profesor)
	{
		$this->load->model('m_profesor_disponibilidad');

		$result = $this->m_profesor_disponibilidad->por_id($id_profesor);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

}
