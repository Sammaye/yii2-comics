function raise_ajax_error(message){
	$('#ajaxErrorModal').find('p.error-message').html(message);
	$('#ajaxErrorModal').modal();
}

$(window).bind('scroll load', function(e){
	if($(window).scrollTop() > 0){
		$('.navbar').addClass('navbar-fixed')
	}else{
		$('.navbar').removeClass('navbar-fixed')
	}
});