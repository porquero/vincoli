function fix_herramientas() {
	var $cache = $('#herramientas');
	if ($(window).scrollTop() > 87)
		$cache.css({'position': 'fixed', 'top': '0'});
	else
		$cache.css({'position': 'relative', 'top': 'auto'});
}

function fix_menu() {
	var $cache = $('#menu');
	if ($(window).scrollTop() > 87)
		$cache.css({position: 'fixed', top: 0});
	else
		$cache.css({position: 'relative', top: 'auto'});
}

$(window).scroll(function() {
	fix_menu();
	fix_herramientas();
});

fix_herramientas();
fix_menu();

$('#crud').tshift();