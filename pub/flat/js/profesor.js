var
				sel_msg = 'Debes elegir un profesor/a primero.',
				del_msg = '¿Seguro que quieres eliminar este profesor/a?';

function llenar_form(row) {
	$('#nombres').val(row.nombres);
	$('#apellidos').val(row.apellidos);
	$('#horas_contrato').val(row.horas_contrato);
	$('#horas_aula').val(row.horas_aula);
	$('#horas_no_lectivas').val(row.horas_no_lectivas);
	$('#horas_permanencia').val(row.horas_permanencia);
	$('#trabajo_tecnico').val(row.trabajo_tecnico);
	$('#comentario').val(row.comentario);

	// Setea los bloques disponibles y la especialidad del profesor.
	$('#crud input[type=checkbox]').prop('checked', false);
	$('#ajax-load').toggleClass('hidden');
	$.ajax({
		url: url_base + 'index.php/horarios/profesor/info/' + row.id
	}).done(function(data) {
		data = $.parseJSON(data);
		disponibilidad = $.parseJSON(data['disponibilidad']);
		disponibilidad_especialidad = $.parseJSON(data['disponibilidad_especialidad']);

		// Marca la disponibilidad del profesor.
		$.each(disponibilidad, function(k, v) {
			$('#' + v.id_check).prop('checked', true);
		});

		// Marca los cursos del profesor.
		if (typeof disponibilidad_especialidad.cursos !== 'undefined') {
			$.each(disponibilidad_especialidad.cursos, function(k, v) {
				$('#curso_' + v.id_curso).prop('checked', true);
			});
		}

		// Marca las asignaturas del profesor.
		if (typeof disponibilidad_especialidad.asignaturas !== 'undefined') {
			$.each(disponibilidad_especialidad.asignaturas, function(k, v) {
				$('#asignatura_' + v.id_asignatura).prop('checked', true);
			});
		}

		// Elimina la especialidad utilizada como template.
		$('.especialidad').first().remove();

		$('#ajax-load').toggleClass('hidden');
	});
}

$('#delegate').on('click', '.listado a', function() {
	var $this = $(this),
					id_profesor = $this.parent().data('profesor');
	window.open(url_base + 'index.php/horarios/profesor/horario/' + id_profesor, '_blank', 'width=800,height=600,location=0,menubar=0,toolbar=0');

	return false;
});