<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class disponibilidad_bloques extends MX_Controller {

	/**
	 * Obtiene disponibilidad del profesor por id
	 *
	 * @param int $id_profesor
	 * @return array/json
	 */
	public function por_id($id_profesor)
	{
		$this->load->model('m_disponibilidad_bloques');

		$result = $this->m_disponibilidad_bloques->por_id($id_profesor);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

}
