$(document).ready(function () {
	var widthYear = $('.yars-chart').find('span').outerWidth(true);
	var lineChart = parseInt($('.top-chart').find('.line-chart').css('left'));
	$('.top-chart').find('.yars-chart span, .yars-chart-slider .swiper-slide').each(function (index) {
		$(this).click(function () {

			if ($(this).hasClass('ready')) {
				var lineChartnew = lineChart + widthYear * index;
				$(this).closest('.top-chart').find('.line-chart').css('left', '' + lineChartnew + 'px');
				$(this).removeClass('ready');
			}

			if ($(this).hasClass('no_click_year')) {} else {
				var lineChartnew = lineChart + widthYear * index;
				$(this).siblings().removeClass('active');
				$(this).addClass('active');
				$(this).closest('.top-chart').find('.line-chart').css('left', '' + lineChartnew + 'px');
				$('.bottom-chart').removeClass('active');
				setTimeout(function () {
					$('.bottom-chart').addClass('null-chart');
				}, 300);
				$('.bottom-chart').css('display', 'none');
				$('.bottom-chart').eq(index).addClass('active');
				setTimeout(function () {
					$('.bottom-chart').eq(index).removeClass('null-chart');
				}, 300);
				$('.bottom-chart').eq(index).fadeIn(200);
			}
		});
	});
	var widthYearCart = $('.yars-chart').find('span').outerWidth(true);
	$('.one-chart-cart').each(function (ind) {
		var lineChartCart = parseInt($('.one-chart-cart').eq(ind).find('.line-chart').css('left'));
		$('.one-chart-cart').eq(ind).find('.top-chart-cart .yars-chart span, .top-chart-cart .yars-chart-slider .swiper-slide').each(function (index) {
			$(this).click(function () {
				if ($(this).hasClass('ready')) {
					lineChartnewCart = lineChartCart + widthYearCart * index;
					$('.one-chart-cart').eq(ind).find('.line-chart').css('left', '' + lineChartnewCart + 'px');
					$(this).removeClass('ready');
				}

				if ($(this).hasClass('no_click_year')) {} else {
					lineChartnewCart = lineChartCart + widthYearCart * index;
					$(this).siblings().removeClass('active');
					$(this).addClass('active');
					$('.one-chart-cart').eq(ind).find('.line-chart').css('left', '' + lineChartnewCart + 'px');
					$(this).closest('.left-chart').find('.bottom-chart-cart').removeClass('active');
					setTimeout(function () {
						$('.one-chart-cart').eq(ind).find('.bottom-chart-cart').addClass('null-chart');
					}, 300);
					$(this).closest('.left-chart').find('.bottom-chart-cart').css('display', 'none');
					$(this).closest('.left-chart').find('.bottom-chart-cart').eq(index).addClass('active');
					setTimeout(function () {
						$('.one-chart-cart').eq(ind).find('.bottom-chart-cart').eq(index).removeClass('null-chart');
					}, 300);
					$(this).closest('.left-chart').find('.bottom-chart-cart').eq(index).fadeIn(200);
				}
			});
		});
	});

	$('.top-chart').children('.yars-chart').find('span.active').trigger('click');
	$('.top-chart').children('.yars-chart').find('span.active').addClass('ready');
	
	$('.top-chart').children('.yars-chart-slider').find('.swiper-slide.active').trigger('click');
	$('.top-chart').children('.yars-chart-slider').find('.swiper-slide.active').addClass('ready');

	$('.one-chart-cart').find('.top-chart-cart').children('.yars-chart').find('span.active').addClass('ready');
	$('.one-chart-cart').find('.top-chart-cart').children('.yars-chart').find('span.active').trigger('click');
	
	$('.one-chart-cart').find('.top-chart-cart .yars-chart-slider').find('.swiper-slide.active').addClass('ready');
	$('.one-chart-cart').find('.top-chart-cart .yars-chart-slider').find('.swiper-slide.active').trigger('click');

});
