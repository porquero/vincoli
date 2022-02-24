<?php



if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Portada de la aplicación
 *
 * @package Vincoli
 * @subpackage Horarios
 * @author Cristian Riffo <criffoh@gmail.com>
 */
class home extends MX_Controller {

    /**
     * App home.
     */
    public function index() {
        $this->tpl->variables(array(
            'title' => 'Vincoli - Gestión de Horarios',
        ));
        $this->tpl->load_view(_TEMPLATE_FILE);
    }

    /**
     * Welcome view.
     */
    public function welcome() {
        echo 'Selecciona un elemento del menú';
    }

}


