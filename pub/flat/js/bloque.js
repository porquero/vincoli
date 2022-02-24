var
				sel_msg = 'Debes elegir un bloque primero.',
				del_msg = '¿Seguro que quieres eliminar este bloque?';

function llenar_form(row) {
	$('#rango').val(row.rango);
	$('#comentario').val(row.comentario);
}