$('#delegate').on('click', '.x_plan', function() {
	quitar_select();
	if (window.confirm('¿Seguro que quieres liberar este bloque?') === true) {
		var $this = $(this);
		$.ajax({
			url: url_base + 'index.php/horarios/curso/liberar_bloque/' + $this.parent().data('bloque')
		}).done(function(data) {
			$this.parent().data('asignatura', '');
			$this.parent().attr('data-asignatura', '');
			$this.parent().data('profesor', '');
			$this.parent().attr('data-profesor', '');
			$this.parent().html(
							'<button class="asignaturas_para_bloque" title="Establecer asignatura" data-bloque="' + $this.parent().data('bloque') + '" ><span></span></button>'
							+ '<button class="profesores_para_bloque" title="Profesores disponibles"><span></span></button>');
		});
	}
});

$('#delegate').on('click', '.asignaturas_para_bloque', function() {
	var $this = $(this);

	quitar_select();
	$this.val('cargando...');

	$.ajax({
		url: url_base + 'index.php/horarios/curso/asignaturas_para_bloque/' + $this.parent().data('bloque')
	}).done(function(data) {
		$this.parent().html(data);
	});
});

$('#delegate').on('change', '.asignatura', function() {
//	if (window.confirm('Se asignará la asignatura.') === true) {
	var $this = $(this);
	var val_select = $this.val().split('_');
	var val_txt = $this.find('option').filter(':selected').text().split(' - ');
	var datos = $this.parent().data('bloque') + '/' + val_select[0] + '/' + val_select[1] + '/'
					+ encodeURIComponent(val_txt[0]) + '/' + encodeURIComponent(val_txt[1]);

	$.ajax({
		url: url_base + 'index.php/horarios/curso/establecer_asignatura/' + datos
	}).done(function(data) {
		$this.parent().data('asignatura', val_select[0]);
		$this.parent().attr('data-asignatura', val_select[0]);
		$this.parent().data('profesor', val_select[1]);
		$this.parent().attr('data-profesor', val_select[1]);
		$this.parent().removeClass('fm-0').addClass('fm-1');
		$this.parent().html(data);
		$this.val('');
	});
//	}
});

$('#h_cnt').on('blur', 'select', quitar_select);

function quitar_select() {
	$('#h_cnt').find('select').each(function() {
		var $this = $(this);
		$this.parent().html(
						'<button class="asignaturas_para_bloque" title="Establecer asignatura" data-bloque="' + $this.parent().data('bloque') + '" ><span></span></button>'
						+ '<button class="profesores_para_bloque" title="Profesores disponibles"><span></span></button>'
						);
	});
}

$('#vaciar_horario').on('click', function() {
	if (window.confirm('Se borrarán todos los bloques del curso.\n¿Seguro que quieres vaciar el horario?') === true) {
		$this = $(this);
		window.location = url_base + 'index.php/horarios/curso/borrar_horario/' + $this.data('id_curso');
	}
});

$('#restablecer_horario').on('click', function() {
	if (window.confirm('Se eliminarán todos los bloques generados por el sistema.\n¿Seguro que quieres restablecer el horario?') === true) {
		$this = $(this);
		window.location = url_base + 'index.php/horarios/curso/restablecer_horario/' + $this.data('id_curso');
	}
});

$('#delegate').on('click', '.profesores_para_bloque', function() {
	var $this = $(this);

	quitar_select();
	$this.val('cargando...');

	$.ajax({
		url: url_base + 'index.php/horarios/curso/bloques_con_profesores_disponibles/' + $this.parent().data('bloque')
	}).done(function(data) {
		$this.parent().html(data);
	});
});

$('#h_cnt').on('mouseenter', 'li b', function() {
	var $this = $(this),
					id_asignatura = $this.parent().parent().data('asignatura');
	$('*[data-asignatura="' + id_asignatura + '"]').addClass('a_hover');
}).on('mouseleave', 'li b', function() {
	var $this = $(this),
					id_asignatura = $this.parent().parent().data('asignatura');
	$('*[data-asignatura="' + id_asignatura + '"]').removeClass('a_hover');
}
);

$('#h_cnt').on('mouseenter', 'li a', function() {
	var $this = $(this),
					id_profesor = $this.parent().data('profesor');
	$('*[data-profesor="' + id_profesor + '"]').addClass('p_hover');
}).on('mouseleave', 'li a', function() {
	var $this = $(this),
					id_profesor = $this.parent().data('profesor');
	$('*[data-profesor="' + id_profesor + '"]').removeClass('p_hover');
});

$('#delegate').on('click', '#resumen li a, .horario li a', function() {
	var $this = $(this),
					id_profesor = $this.parent().data('profesor');
	window.open(url_base + 'index.php/horarios/profesor/horario/' + id_profesor, 'Horario del profesor', 'width=800,height=600,location=0,menubar=0,toolbar=0,top=90,left=50');

	return false;
});

$(window).off('scroll');