var
				sel_msg = 'Debes elegir un curso primero.',
				del_msg = '¿Seguro que quieres eliminar este curso?',
				plan_tpl = $('.plan').first().clone();

setear_plan = function(asignatura, horas, id_profesor) {
	var otro_plan = plan_tpl.clone();
	var row = $('.listado').find("input[type=radio]:checked").data('row');

	if (id_profesor > 0) {
		$.ajax({
			url: url_base + 'index.php/horarios/curso/profesores_para_asignatura/' + row.id + '/' + asignatura
		}).done(function(data) {
			otro_plan.find('.profesor_asignatura').html(data);
			otro_plan.find('.profesor_asignatura').find('select').val(id_profesor);
			otro_plan.find('.profesor_asignatura').removeClass();
		});

	}

	otro_plan.find('.asignatura').val(asignatura);
	otro_plan.find('.horas').val(horas);
	otro_plan.appendTo($('#plan_estudio'));

	return otro_plan;
};

resetear_planes = function() {
	$('.plan').remove();
	plan_tpl.appendTo($('#plan_estudio'));
};

$('#agregar_plan').on('click', function() {
	setear_plan('', '', 0).find('.asignatura').focus();
});

$('#plan_estudio').on('click', '.x_plan', function() {
	var $this = $(this);
	$this.parent().remove();
});

$('#plan_estudio').on('keydown', '.horas:last', function(e) {
	e.stopPropagation();

	if (event.keyCode === 9) {
		setear_plan('', '', 0).find('.asignatura').focus();

		return false;
	}
});

function llenar_form(row) {
	$('#glosa').val(row.glosa);
	$('#id_profesor').val(row.id_profesor);
	$('#comentario').val(row.comentario);

	// Setea el plan de estudios del curso.
	resetear_planes();
	$('#ajax-load').toggleClass('hidden');

	$.ajax({
		url: url_base + 'index.php/horarios/curso/info/' + row.id
	}).done(function(data) {
		data = $.parseJSON(data);
		plan_estudio = $.parseJSON(data['plan_estudio']);
		horario = $.parseJSON(data['horario']);

		// Marca los bloques del horario del curso.
		$.each(horario, function(k, v) {
			$('#' + v.id_check).prop('checked', true);
		});

		// Agrega los planes de estudio del curso.
		$.each(plan_estudio, function(k, v) {
			setear_plan(v.id_asignatura, v.horas, v.id_profesor);
		});

		// Elimina el plan utilizado como template.
		$('.plan').first().remove();

		$('#glosa').focus();
		$('#ajax-load').toggleClass('hidden');
	});
}

$('#agregar').on('click', function() {
	resetear_planes();
});

$('#delegate').on('click', '.profesor_asignatura', function() {
	var $this = $(this);
	var row = $('.listado').find("input[type=radio]:checked").data('row');
	if (row === null) {
		alert("El curso no tiene profesores asociados aún para esta asignatura.");
		return false;
	}
	var id_asignatura = $this.parents('.plan').find('.asignatura').val();

	$this.find('input').val('cargando...');

	$.ajax({
		url: url_base + 'index.php/horarios/curso/profesores_para_asignatura/' + row.id + '/' + id_asignatura
	}).done(function(data) {
		$this.html(data);
		$this.removeClass();
	});
});

$('#editar_horario').on('click', function(e) {
	e.preventDefault();
	if ($('.listado').find("input[type=radio]:checked").length === 0) {
		alert(sel_msg);
	} else {
		window.location = url_base + 'index.php/horarios/curso/editar_horario/' + $('.listado').find("input[type=radio]:checked").val();
	}
});