<?php

if ( ! defined('BASEPATH')) {
		exit('No direct script access allowed');
}

/**
 * Clase para trabajar con transacciones cuando se trabaja con varios módulos.
 *
 * @package Playa
 * @subpackage Tools
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class M_transaction extends CI_Model {

		public function __construct()
		{
				parent::__construct();
				$this->db = $this->load->database('', TRUE);
		}

		/**
		 * Inicia la transacción.
		 *
		 * @return void
		 */
		public function start()
		{
				$this->db->trans_start(TRUE);
		}

		/**
		 * Finaliza la transacción.
		 *
		 * @return void
		 */
		public function complete()
		{
				return $this->db->trans_complete();
		}

}
