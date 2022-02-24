<?php

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class plan_estudio extends MX_Controller {

	/**
	 * Obtiene plan de estudio del curso por id
	 *
	 * @param int $id_curso
	 * @return array/json
	 */
	public function por_id($id_curso)
	{
		$this->load->model('m_plan_estudio');

		$result = $this->m_plan_estudio->del_curso($id_curso);

		if ($this->input->is_ajax_request()) {
			echo json_encode($result);
		}
		else {
			return $result;
		}
	}

}
