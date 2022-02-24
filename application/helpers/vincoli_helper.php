<?php

function ordernar_por_hora($a, $b) {
    if ($a['horas'] == $b['horas']) {
        return 0;
    }
    return ($a['horas'] < $b['horas']) ? -1 : 1;
}

/**
 * Hack para evitar error amigable en Chrome&IE  cuando lo enviado al navegador es < 512 bytes.
 * 
 * @return string
 */
function hack_512()
{
	return str_repeat(' ', 512 * 2);
}
