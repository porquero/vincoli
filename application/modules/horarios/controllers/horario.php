<?php

ini_set('memory_limit', '512M');

if ( ! defined('BASEPATH'))
	exit('No direct script access allowed');

/**
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class horario extends MX_Controller {

	/**
	 * Template de archivo excel para generar archivo con horarios de los profesores.
	 *
	 * @var string
	 */
	private $_template_horario_profesor = '/../views/horario/horario_profesor.xls';

	/**
	 * Nombre de archivo de descarga para horarios de los profesores
	 *
	 * @var string
	 */
	private $_archivo_descarga_horario_profesores = 'horarios_profesores.xlsx';

	/**
	 * Template de archivo excel para generar archivo con horarios de los cursos.
	 *
	 * @var string
	 */
	private $_template_horario_curso = '/../views/horario/horario_curso.xls';

	/**
	 * Nombre de archivo de descarga para horarios de los cursos
	 *
	 * @var string
	 */
	private $_archivo_descarga_horario_cursos = 'horarios_cursos.xlsx';

	/**
	 * Portada de horarios.
	 */
	public function index()
	{
		$this->tpl->variables(array(
				'title' => 'Generador de horarios',
		));

		$this->tpl->section('_view', 'index.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Genera el horario para el curso.
	 * El procedimiento que realiza es el siguiente:
	 * Obtiene el plan de estudio preparado para ser utilizado en el completado del horario.
	 * Luego obtiene los profesores que están disponible para el plan de estudio. Así aseguramos que una asignatura
	 * sólo sea dictada por un profesor.
	 * Luego se va completando el horario según disponibilidad del profesor.
	 *
	 * @param int $id_curso
	 */
	public function generar_curso($id_curso)
	{
		$this->load->helper('url');

		$this->output->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0', FALSE);
		$this->output->set_header('Pragma: no-cache');

		$this->_generar_curso($id_curso);

		redirect('horarios/curso/editar_horario/' . $id_curso);
	}

	/**
	 * Genera el horario para el curso enviado.
	 *
	 * @param integer $id_curso
	 */
	private function _generar_curso($id_curso)
	{
		//		$this->output->enable_profiler();

		$this->load->model('m_horario');
		$this->load->model('m_plan_estudio');
		// Contador para intentar 3 veces generar el horario al 100%
		$loop = 0;

		do {
			++ $loop;

			// Vacía los bloques generados por sistema.
			$this->m_horario->restablecer_del_curso($id_curso);
			sleep(3);

			$plan_estudio_preparado = $this->_plan_estudio_preparado($id_curso);
//			echo '<p>$plan_estudio_preparado</p>';
//			\Plogger::var_dump($plan_estudio_preparado);
			$profesores_disponibles = $this->profesores_disponibles_para_plan_estudio($id_curso);
//			echo '<p>$profesores_disponibles</p>';
//			\Plogger::var_dump($profesores_disponibles);
			// Agrega asignaturas con bloques contiguos primero.
			$asignaturas_faltantes = $this->guardar_asignatura($id_curso, $plan_estudio_preparado
				, $profesores_disponibles, TRUE);

			// Agrega asignaturas con bloques lejanos. Se utiliza en el caso de profesores que no tengan bloques contiguos disponibles.
			$asignaturas_fuera = $this->guardar_asignatura($id_curso, $asignaturas_faltantes
				, $profesores_disponibles, FALSE);

//		// Movemos los profesores para que los que faltan puedan tener un bloque en el horario.
			foreach ($asignaturas_fuera as $asignatura) {

//				echo '<p>$asignatura(todas)</p>';
//				Plogger::var_dump($asignatura);
				if ($asignatura['horas'] > 0) {
//				echo '<p>$asignatura(con horas)</p>';
//				Plogger::var_dump($asignatura);
					$this->_enrocar($id_curso, $this->m_horario->profesores_disponibles_para_asignatura(
							$id_curso, $asignatura['id_asignatura'], $asignatura['horas']), $asignatura['horas']);
				}
			}
			sleep(4);
		} while ($this->porcentaje($id_curso) < 100 && $loop < 5);
	}

	/**
	 * Genera todos los horarios
	 */
	public function generar()
	{
		echo 'INICIANDO<br>';

//		if (ENVIRONMENT == 'development') {
//			$this->output->enable_profiler();
//		}

		$this->output->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		$this->output->set_header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
		$this->output->set_header('Cache-Control: post-check=0, pre-check=0', FALSE);
		$this->output->set_header('Pragma: no-cache');

		$this->load->model('m_curso');
		$this->load->model('m_horario');
		$this->load->model('m_transaction');

		$this->m_transaction->start();

		foreach ($this->m_curso->fetch() as $curso) {
			echo "Generando para el curso: {$curso->glosa}<br>";
			$this->_generar_curso($curso->id);
		}

		$this->m_transaction->complete();

		echo 'FINALIZADO<br>';

//        $this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Guarda la asignatura en el horario para el curso
	 *
	 * @param int $id_curso
	 * @param array $plan_estudio
	 * @param array $profesores_disponibles
	 * @param bool $bloques_contiguos
	 * @return array
	 */
	private function guardar_asignatura($id_curso, $plan_estudio, $profesores_disponibles, $bloques_contiguos)
	{
		$this->load->model('m_profesor');

		foreach ($plan_estudio as $k => $plan) {
			$datos = array();

			if (isset($profesores_disponibles[$plan['id_asignatura']]) === FALSE) {
				continue;
			}

			// Dejamos los bloques de 1 hora para el final.
			if ($plan['horas'] == 1 && $bloques_contiguos == TRUE) {
				continue;
			}

			$disponibilidad = $this->m_horario->bloques_disponibles_profesor($id_curso, $profesores_disponibles[$plan['id_asignatura']]
				, $plan['horas'], $bloques_contiguos, $plan['id_asignatura']);

			foreach ($disponibilidad as $bloque) {
				$saltar = $this->verificar_bloques_cursos_faltantes($id_curso, $bloque);

				if ($saltar === TRUE) {
					continue;
				}

				$datos[] = array(
						'id_curso' => $id_curso,
						'id_bloque' => $bloque->id_bloque,
						'id_dia' => $bloque->id_dia,
						'id_asignatura' => $plan['id_asignatura'],
						'id_profesor' => $bloque->id_profesor,
				);
			}

			if (count($datos) > 0) {
				$this->m_horario->agregar_asignatura($datos);
				if ($bloques_contiguos === FALSE) {
					$plan_estudio[$k]['horas'] = $plan_estudio[$k]['horas'] - count($datos);
					if ($plan_estudio[$k]['horas'] == 0) {
						unset($plan_estudio[$k]);
					}
				}
				else {
					unset($plan_estudio[$k]);
				}
			}
		}

		return $plan_estudio;
	}

	/**
	 * Retorna arreglo con las asignaturas preparadas para ser asignadas a un profesor ya que están
	 * divididas por la cantidad de horas máximas que puede tener un bloque (2 horas).
	 * Este arreglo es utilizado luego para asociarlo a un bloque disponible de un profesor que dicte la
	 * asignatura en el curo, en el método generar_curso().
	 *
	 * @param int $id_curso
	 * @return array
	 */
	private function _plan_estudio_preparado($id_curso)
	{
		$this->load->model('m_plan_estudio');
		$this->load->model('m_curso');
		$asignaturas = $this->m_plan_estudio->del_curso($id_curso);
		$horas_horario = $this->m_curso->horas_horario($id_curso);

		foreach ($asignaturas as $k => $asignatura) {
			if (array_key_exists($asignatura->id_asignatura, $horas_horario)) {
				$asignaturas[$k]->horas = $asignaturas[$k]->horas - $horas_horario[$asignatura->id_asignatura];
			}
		}

		$asignaturas_preparadas = array();
		foreach ($asignaturas as $k_asig => $asignatura) {
			while ($asignatura->horas > 0) {
				$horas = $asignatura->horas - 2 < 0 ? 0 : $asignatura->horas - 2;
				$asignaturas_preparadas[] = array(
						'id_asignatura' => $asignatura->id_asignatura,
						'glosa' => $asignatura->glosa,
						'horas' => $asignatura->horas == 1 ? 1 : 2,
				);
				$asignaturas[$k_asig]->horas = $horas;
			}
		}

		// Desordenamos el horario para tener varias soluciones si decide generar nuevamente.
		shuffle($asignaturas_preparadas);

		return $asignaturas_preparadas;
	}

	/**
	 * Retorna el plan de estudio del curso.
	 *
	 * @param int $id_curso
	 * @return array
	 *
	 * @deprecated since version 0.12
	 */
	private function _plan_estudio($id_curso)
	{
		$this->load->model('m_horario');
		return $this->m_horario->asignaturas_del_curso($id_curso);
	}

	/**
	 * Muestra el horario para el curso.
	 * @param int $id_curso
	 */
	public function del_curso($id_curso)
	{
		$this->_vista_curso($id_curso);

		$this->tpl->section('_view', 'del_curso.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Obtiene los datos del curso para generar el horario.
	 *
	 * @param int $id_curso
	 *
	 * @return string html del horario generado
	 */
	private function _vista_curso($id_curso)
	{

		$this->tpl->variables($this->datos_vista_curso($id_curso));
		$this->tpl->section('_view_', 'del_curso.phtml', TRUE);

		return $this->tpl->load_view('BLANK_txt.phtml', array(), TRUE);
	}

	/**
	 * Carga los datos para generar la vista para el horario del curso.
	 *
	 * @param int $id_curso
	 *
	 * @return array Arreglo con las variables a utilizar en la vista para ser utilizado en otra acción.
	 */
	public function datos_vista_curso($id_curso)
	{
		$this->load->model('m_horario');
		$this->load->model('m_curso');
		$variables_vista = array(
				'title' => 'Horario del curso',
				'head' => link_tag('css/horario.css'),
				'asignaturas' => $this->m_horario->del_curso($id_curso),
				// @TODO: Estos datos deben sacarse de la bd.
				'nombre_dias' => array('lunes', 'martes', 'miércoles', 'jueves', 'viernes'),
				'numero_bloques' => range(1, 9),
				'detalle_curso' => $this->m_curso->info($id_curso),
		);

		return $variables_vista;
	}

	/**
	 * Horario de todos los cursos.
	 */
	public function cursos()
	{
		$this->load->model('m_curso');

		$cursos = array();

		foreach ($this->m_curso->fetch() as $curso) {
			$cursos[] = $this->_vista_curso($curso->id);
		}

		$this->tpl->variables(array(
				'title' => 'Horario de todos los cursos',
				'cursos' => $cursos,
		));

		$this->tpl->variables('head', link_tag('css/horario_cursos.css'), TRUE);

		$this->tpl->section('_view', 'cursos.phtml');
		$this->tpl->load_view(_TEMPLATE_FILE);
	}

	/**
	 * Elimina todos los horarios generados.
	 * @TODO Implementar un backup.
	 */
	public function vaciar()
	{
		$this->load->model('m_horario');
		$this->m_horario->vaciar();

		redirect();
	}

	/**
	 * Retorna los profesores disponibles para el plan de estudio del curso.
	 *
	 * @param int $id_curso
	 * @return array
	 *
	 * @todo se puede disminuir la cantidad de consultas enviando el plan de estudio del curso por parámetro.
	 */
	public function profesores_disponibles_para_plan_estudio($id_curso)
	{
		$this->load->model('m_horario');
		$this->load->model('m_plan_estudio');

		$plan_estudio = $this->_plan_estudio_preparado($id_curso);

		return $this->m_horario->profesores_disponibles_para_plan_estudio($plan_estudio, $id_curso, $this->m_plan_estudio);
	}

	/**
	 * Sólo test.
	 */
	public function test()
	{
		echo '<pre>';
		var_dump($this->profesores_disponibles_para_plan_estudio(2));
	}

	/**
	 * Quita todos los bloques de los horarios generados por sistema.
	 */
	public function restablecer()
	{
		$this->load->model('m_horario');
		$this->m_horario->restablecer();
	}

	/**
	 * Trata de mover un profesor de un bloque a otro disponible si está ocupando un bloque disponible de los
	 * profesores faltantes.
	 *
	 * @param integer $id_curso
	 * @param array $profesores_disponibles
	 *
	 * @return void
	 */
	private function _enrocar($id_curso, $profesores_disponibles, $asignatura_horas_faltantes)
	{
		$this->load->model('m_horario');
		$this->load->model('m_profesor');

		$bloques_libres = Modules::run('horarios/curso/bloques_con_profesores_disponibles', $id_curso);
//		Plogger::var_dump($bloques_libres);
//		Plogger::var_dump($profesores_disponibles);

		foreach ($bloques_libres as $bloque_libre) {
			// Índice para eliminar profesor disponible si es que se logró agregar al horario.
			$i = 0;

			foreach ($profesores_disponibles as $profesor_dia_bloque) {
//				Plogger::var_dump($i);
//				Plogger::var_dump($asignatura_horas_faltantes);
				// LImita a sólo la cantidad de horas faltantes.
				if (0 === $asignatura_horas_faltantes) {
					return;
				}

				// Intenta mover las asignaturas.
				if ($this->enroque($id_curso, $profesor_dia_bloque, $bloque_libre) === TRUE) {
					unset($profesores_disponibles[$i]);
					$this->_enrocar($id_curso, $profesores_disponibles, -- $asignatura_horas_faltantes);

					return;
				}

				$i ++;
			}
		}
	}

	/**
	 * Intenta mover al profesor que está utilizando el bloque disponible para el profesor de la asignatura faltante.
	 *
	 * @param integer $id_curso
	 * @param array $profesor_dia_bloque  Profesor faltante con bloque disponible. [id_dia, id_bloque, id_asignatura, id_profesor]
	 * @param array $bloque_libre Bloque libre del curso. [profesor]->(obj){id_dia, id_bloque}
	 *
	 * @return boolean
	 */
	public function enroque($id_curso, $profesor_dia_bloque, $bloque_libre)
	{
		$info_bloque = $this->m_horario->info_bloque($id_curso, $profesor_dia_bloque['id_dia']
			, $profesor_dia_bloque['id_bloque']);

		if (is_array($info_bloque) === TRUE) {
			return FALSE;
		}

		// No mueve al profesor si está fijado manualmente. @todo: hacerlo configurable.
		if ($info_bloque->fijado_manualmente == 1) {
			return FALSE;
		}

		$disponibilidad = $this->m_profesor->mapa_disponibilidad_actual($info_bloque->id_profesor);

//				Plogger::var_dump($bloque_libre);
//				Plogger::var_dump($info_bloque->id_profesor);
//				Plogger::var_dump($disponibilidad);
		if (isset($disponibilidad[$bloque_libre['profesor']->id_dia][$bloque_libre['profesor']->id_bloque])) {
//					Plogger::var_dump($disponibilidad);
//					Plogger::var_dump("{$profesor_dia_bloque['id_dia']}, {$profesor_dia_bloque['id_bloque']}");
			// Evita que se repita una asignatura en el mismo día.
			if ($this->m_horario->limitar_en_dia($id_curso, $info_bloque->id_asignatura, $bloque_libre['profesor']->id_dia) === TRUE OR $this->m_horario->limitar_en_bloque($id_curso, $info_bloque->id_asignatura, $bloque_libre['profesor']->id_bloque) == TRUE) {
				return FALSE;
			}

			// Movemos al profesor que está ocupando el bloque disponible del profesor faltante.
			$this->m_horario->vaciar_bloque($id_curso, $profesor_dia_bloque['id_dia'], $profesor_dia_bloque['id_bloque']);
			$datos = array(
					'id_curso' => $id_curso,
					'id_bloque' => $bloque_libre['profesor']->id_bloque,
					'id_dia' => $bloque_libre['profesor']->id_dia,
					'id_asignatura' => $info_bloque->id_asignatura,
					'id_profesor' => $info_bloque->id_profesor,
			);
//					Plogger::var_dump($datos);
			$this->m_horario->llenar_bloque($datos);

			// Agregamos al profesor faltante.
			$datos = array(
					'id_curso' => $id_curso,
					'id_bloque' => $profesor_dia_bloque['id_bloque'],
					'id_dia' => $profesor_dia_bloque['id_dia'],
					'id_asignatura' => $profesor_dia_bloque['id_asignatura'],
					'id_profesor' => $profesor_dia_bloque['id_profesor'],
			);
//					Plogger::var_dump($datos);
			$this->m_horario->llenar_bloque($datos);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Retorna porcentaje completado del horario del curso.
	 *
	 * @param integer $id_curso
	 *
	 * @return integer
	 */
	public function porcentaje($id_curso)
	{
		$this->load->model('m_horario');
		$this->load->model('m_plan_estudio');

		return ceil(100 * $this->m_horario->total_horas($id_curso) / $this->m_plan_estudio->total_horas($id_curso));
	}

	/**
	 * Genera archivo excel con los horarios de los profesores y lo envía para descargar
	 */
	public function descargar_profesores()
	{
		$this->load->model('m_profesor');
		$this->load->model('m_bloque');

		// @TODO: Estos datos deben sacarse de la bd.
		$nombre_dias = array('lunes', 'martes', 'miércoles', 'jueves', 'viernes');
		$numero_bloques = range(1, 9);
		$archivo_salida = BASEPATH . '../tmp/' . $this->_archivo_descarga_horario_profesores;
		$profesores = $this->m_profesor->fetch();
		$bloques = $this->m_bloque->fetch();

		// Si el archivo está lo descargamos sin generar. El archivo no estaría cuando se inserte/modifique un registro.
//		if (is_file($archivo_salida) === TRUE) {
//			$this->_enviar_archivo($this->_archivo_descarga_horario_profesores, $archivo_salida);
//			return;
//		}

		include_once BASEPATH . '/../' . APPPATH . 'libraries/PHPExcel/Classes/PHPExcel/IOFactory.php';

		// Cambiamos la forma de almacenar el caché para no llenar la memoria
		$cache_method = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
		$cache_settings = array('dir' => BASEPATH . '../tmp/');
		PHPExcel_Settings::setCacheStorageMethod($cache_method, $cache_settings);

		// Generamos el excel resultado.
		$obj_reader = PHPExcel_IOFactory::createReader('Excel5');
		$obj_php_excel_archivo_final = $obj_reader->load(dirname(__FILE__) . $this->_template_horario_profesor);
		$obj_php_excel_archivo_final->getActiveSheet()->setTitle(uniqid());

		// Agregamos horarios por profesor.
		$hoja_actual = 1;
		foreach ($profesores as $profesor) {
			$bloques_disponibles = $this->m_profesor->horario($profesor->id);
			$d = 0;

			$obj_php_excel_profesor = $obj_reader->load(dirname(__FILE__) . $this->_template_horario_profesor);

			$obj_php_excel_archivo_final->addExternalSheet($obj_php_excel_profesor->setActiveSheetIndex());

			// Usamos la hoja actual y aumentamos el índice para la próxima.
			$obj_php_excel_archivo_final->setActiveSheetIndex($hoja_actual ++ );
			$obj_php_excel_archivo_final->getActiveSheet()->setTitle($profesor->nombre_profesor);

			// Nombre del profesor.
			$obj_php_excel_archivo_final->getActiveSheet()->setCellValue('D5', $profesor->nombre_profesor);

			// Curso jefatura
			$obj_php_excel_archivo_final->getActiveSheet()->setCellValue('G5', $profesor->curso);

			$column = 1;
			foreach ($nombre_dias as $dia) {
				$d ++;
				$base_row = 9;
				++ $column;

				foreach ($numero_bloques as $bloque) {
					if (isset($bloques_disponibles[$d][$bloque])) {
						if (is_null($bloques_disponibles[$d][$bloque]->asignatura) === TRUE) {
							$disponibilidad = '✔';
						}
						else {
							$datos = <<<PQR
{$bloques_disponibles[$d][$bloque]->curso}
- {$bloques_disponibles[$d][$bloque]->asignatura}
PQR;
							$disponibilidad = isset($bloques_disponibles[$d][$bloque]) ? $datos : '';
						}
					}
					else {
						$disponibilidad = isset($bloques_disponibles[$d][$bloque]) ? '✔' : '-';
					}

					// Agregamos datos del bloque
					$obj_php_excel_archivo_final->getActiveSheet()
						->setCellValue(PHPExcel_Cell::stringFromColumnIndex(1) . $base_row, $bloques[$bloque - 1]->rango)
						->setCellValue(PHPExcel_Cell::stringFromColumnIndex($column) . $base_row ++, $disponibilidad)
					;
				}
			}
		}

		$obj_php_excel_archivo_final->removeSheetByIndex(0);
		$obj_php_excel_archivo_final->setActiveSheetIndex(0);

		$obj_writer = PHPExcel_IOFactory::createWriter($obj_php_excel_archivo_final, 'Excel2007');
		$obj_writer->save($archivo_salida);

		$this->_enviar_archivo($this->_archivo_descarga_horario_profesores, $archivo_salida);
	}

	/**
	 * Genera archivo excel con los horarios de los cursos y lo envía para descargar
	 */
	public function descargar_cursos()
	{
		$this->load->model('m_curso');
		$this->load->model('m_bloque');
		$this->load->model('m_horario');

		// @TODO: Estos datos deben sacarse de la bd.
		$nombre_dias = array('lunes', 'martes', 'miércoles', 'jueves', 'viernes');
		$numero_bloques = range(1, 9);
		$archivo_salida = BASEPATH . '../tmp/' . $this->_archivo_descarga_horario_cursos;
		$cursos = $this->m_curso->fetch();
		$bloques = $this->m_bloque->fetch();

		// Si el archivo está lo descargamos sin generar. El archivo no estaría cuando se inserte/modifique un registro.
//		if (is_file($archivo_salida) === TRUE) {
//			$this->_enviar_archivo($this->_archivo_descarga_horario_cursos, $archivo_salida);
//			return;
//		}

		include_once BASEPATH . '/../' . APPPATH . 'libraries/PHPExcel/Classes/PHPExcel/IOFactory.php';

		// Cambiamos la forma de almacenar el caché para no llenar la memoria
		$cache_method = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
		$cache_settings = array('dir' => BASEPATH . '../tmp/');
		PHPExcel_Settings::setCacheStorageMethod($cache_method, $cache_settings);

		// Generamos el excel resultado.
		$obj_reader = PHPExcel_IOFactory::createReader('Excel5');
		$obj_php_excel_archivo_final = $obj_reader->load(dirname(__FILE__) . $this->_template_horario_curso);
		$obj_php_excel_archivo_final->getActiveSheet()->setTitle(uniqid());

		// Agregamos horarios por curso.
		$hoja_actual = 1;
		foreach ($cursos as $curso) {
			$asignaturas = $this->m_horario->del_curso($curso->id);
			$d = 0;

			$obj_php_excel_curso = $obj_reader->load(dirname(__FILE__) . $this->_template_horario_curso);

			$obj_php_excel_archivo_final->addExternalSheet($obj_php_excel_curso->setActiveSheetIndex());

			// Usamos la hoja actual y aumentamos el índice para la próxima.
			$obj_php_excel_archivo_final->setActiveSheetIndex($hoja_actual ++ );
			$obj_php_excel_archivo_final->getActiveSheet()->setTitle($curso->glosa);

			// Nombre del profesor jefe.
			$obj_php_excel_archivo_final->getActiveSheet()->setCellValue('F5', $curso->profesor_jefe);

			// Curso.
			$obj_php_excel_archivo_final->getActiveSheet()->setCellValue('C5', $curso->glosa);

			$column = 1;
			foreach ($nombre_dias as $dia) {
				$d ++;
				$base_row = 9;
				++ $column;

				foreach ($numero_bloques as $bloque) {
					$asignatura = isset($asignaturas[$dia][$bloque]['asignatura']) ? $asignaturas[$dia][$bloque]['asignatura'] : '';
					$profesor = isset($asignaturas[$dia][$bloque]['profesor']) ? $asignaturas[$dia][$bloque]['profesor'] : '';

					$txt_bloque = <<<PQR
{$asignatura}
- {$profesor}
PQR;

					// Agregamos datos del bloque
					$obj_php_excel_archivo_final->getActiveSheet()
						->setCellValue(PHPExcel_Cell::stringFromColumnIndex(1) . $base_row, $bloques[$bloque - 1]->rango)
						->setCellValue(PHPExcel_Cell::stringFromColumnIndex($column) . $base_row ++, $txt_bloque)
					;
				}
			}
		}

		$obj_php_excel_archivo_final->removeSheetByIndex(0);
		$obj_php_excel_archivo_final->setActiveSheetIndex(0);

		$obj_writer = PHPExcel_IOFactory::createWriter($obj_php_excel_archivo_final, 'Excel2007');
		$obj_writer->save($archivo_salida);

		$this->_enviar_archivo($this->_archivo_descarga_horario_cursos, $archivo_salida);
	}

	/**
	 * Headers y contenido de archivo a descargar.
	 * 
	 * @param string $nombre_archivo Nombre del archivo a descargar
	 * @param string $archivo_salida Ruta absoluta del archivo a descargar
	 * 
	 * @return void Envía resultado al navegador
	 */
	protected function _enviar_archivo($nombre_archivo, $archivo_salida)
	{
		header('Content-type: application/vnd.ms-excel');
		header("Content-Disposition: attachment; filename={$nombre_archivo}");
		header('Pragma: no-cache');

		echo file_get_contents($archivo_salida);
		echo hack_512();
	}

	/**
	 * Verifica si los cursos del profesor quedarán con bloques sin disponibilidad de profesores. Si es as{i retorna
	 * TRUE para que el invocador tome la decisión correspondiente.
	 *
	 * @param integer $id_curso
	 * @param obj $bloque
	 * 
	 * @return boolean
	 */
	public function verificar_bloques_cursos_faltantes($id_curso, $bloque)
	{
		$cursos_profesor = $this->m_profesor->cursos($bloque->id_profesor, TRUE);

		foreach ($cursos_profesor as $curso) {
			if ($this->porcentaje($curso->id) == 100) {
				continue;
			}
//					Plogger::var_dump(count(Modules::run('horarios/curso/bloques_con_profesores_disponibles', $curso->id, $bloque->id_dia, $bloque->id_bloque)));
			if (count(Modules::run('horarios/curso/bloques_con_profesores_disponibles', $curso->id, $bloque->id_dia, $bloque->id_bloque)) === 0 && $id_curso !== $curso->id) {
				Plogger::log('Curso casi se queda sin profesor disponible. Se omite. ID: ' . $curso->id);
				return TRUE;
			}
		}

		return FALSE;
	}

}
