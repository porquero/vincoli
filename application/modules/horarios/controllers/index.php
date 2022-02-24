<?php

if ( ! defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 *
 * @package
 * @subpackage
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class index extends MX_Controller {

	public function portada()
	{
		$this->load->model('m_estadisticas');
		$this->load->model('m_curso');


		$this->tpl->variables(array(
				'title' => 'Resumen',
				'head' => link_tag('pub/' . _TEMPLATE_NAME . '/css/horario.css')
				. link_tag('pub/' . _TEMPLATE_NAME . '/css/index_portada.css'),
				'total_cursos' => $this->m_estadisticas->total_cursos(),
				'profesores' => $this->m_estadisticas->total_profesores(),
				'horas_planes_estudios' => $this->m_estadisticas->total_horas_planes_estudios(),
				'horas_profesores' => $this->m_estadisticas->total_horas_profesores(),
				'nombre_dias' => array('lunes' => 1, 'martes' => 2, 'miércoles' => 3, 'jueves' => 4, 'viernes' => 5),
				'numero_bloques' => range(1, 9),
				'cursos' => $this->m_curso->fetch(),
				'c_curso' => $this->load->module('horario/curso'),
		));

		$this->tpl->section('_view', 'portada.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
		
	}

}
