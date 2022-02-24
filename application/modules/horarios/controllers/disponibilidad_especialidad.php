<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class disponibilidad_especialidad extends MX_Controller {

	/**
	 * Obtiene especialidad de un profesor por su id.
	 *
	 * @param int $id_profesor
	 * @return mixed json/array
	 */
	public function por_id($id_profesor)
	{
		$this->load->model('m_disponibilidad_especialidad');

		$result = $this->m_disponibilidad_especialidad->por_id($id_profesor);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

}
