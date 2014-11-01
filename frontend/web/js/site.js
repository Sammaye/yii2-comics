$(window).scroll(function(e){
	if($(window).scrollTop() > 0){
		$('.navbar').addClass('navbar-fixed')
	}else{
		$('.navbar').removeClass('navbar-fixed')
	}
});