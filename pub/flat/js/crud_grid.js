// Main

$('#delegate').on('click', '.x_form', function() {
	$('#crud').slideUp();
});

$('#crud').tshift();

$('#delegate').on('click', '.listado tbody tr', function() {
	var $this = $(this);
	var radio = $this.find("input[type=radio]").first();
	radio.prop("checked", !radio.is(':checked'));
	$('#crud').slideUp({duration: 500});
});

$('#fcrud').on('keypress', 'input:not([type=submit])', function(event) {
	if (event.keyCode === 10 || event.keyCode === 13)
		event.preventDefault();
});

$('#delegate').on('keydown', 'input[type=number]', function(e) {
	var $this = $(this);
	var key = e.which || e.keyCode || e.charCode;

	if (key === 8) {
		var s = $this.val();
		s = s.substring(0, s.length - 1);
		$this.val(s);
		return false;
	}
});

// CRUD
var
				sel_msg = 'Debes elegir un registro.',
				del_msg = '¿Seguro que quieres eliminar este registro?';

$('#agregar').on('click', function() {
	$(this).blur();
	$('.listado').find("input[type=radio]").prop('checked', false);
	$('#crud h3').text($(this).val());

	$('#crud input[type=checkbox]').prop('checked', false);
	$('#crud :input:not(:submit):not(:button):not(:checkbox)').val('');
	$('select').val('');
	$('textarea').val('');

	$('#enviar').val($(this).val());
	$('#crud').slideDown();

	$('html, body').animate({
		scrollTop: $("#bgt").offset().top
	}, 500);
});

$('#eliminar').on('click', function(e) {
	if ($('.listado').find("input[type=radio]:checked").length === 0) {
		e.preventDefault();
		alert(sel_msg);
	} else {
		$('#crud').slideUp();
		if (window.confirm(del_msg) === true) {
			$('#crud').remove();
		} else {
			e.preventDefault();
		}
	}
});

$('#modificar').on('click', function() {
	$(this).blur();
	if ($('.listado').find("input[type=radio]:checked").length === 1) {
		$('#crud h3').text($(this).val());

		var row = $('.listado').find("input[type=radio]:checked").data('row');
		llenar_form(row);

		$('#enviar').val($(this).val());
		$('#crud').slideDown();

		$('html, body').animate({
			scrollTop: $("#bgt").offset().top
		}, 500);
	} else {
		alert(sel_msg);
	}
});