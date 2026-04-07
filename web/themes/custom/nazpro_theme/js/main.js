$(function ($) {
	var heghtInfo;

	function heightInfotab(heghtInfo) {
		var heghtMax = $('.hid-info-tab').height();
		if (heghtInfo > heghtMax) {
			$(".hid-info-tab").mCustomScrollbar();
		}
	}

	function placeh(place_tag) {
		$(place_tag).focus(function () {
			$(this).attr("placeholder", "");
		}).blur(function () {
			$(this).attr("placeholder", $(this).data('empty'));
		}).each(function () {
			$(this).attr("placeholder", $(this).data('empty'));
		});
	}
	$(window).on("load", function () {
		$('.all-chart').css('display', 'block');
		//setTimeout(function() {
		$('.one-chart-cart').each(function (i) {
			var heightSmall = $('.one-chart-cart').eq(i).find('.bottom-chart-cart').height();
			var heightLeft = $('.one-chart-cart').eq(i).outerHeight();
			var heightTopLeft = $('.one-chart-cart').eq(i).find('.top-chart-cart').height();
			//console.log(heightLeft + ' -чарт  ' + heightSmall + ' -слайд ' + heightTopLeft);
			if (heightLeft < heightSmall) {
				$('.one-chart-cart').eq(i).find('.all-chart').height(heightSmall + 20);
			} else {
				$('.one-chart-cart').eq(i).find('.all-chart').height(heightLeft - heightTopLeft + 20);
			}
		});
		if ($('body').height() < $(window).height()) {
			$('footer').css({
				'position': 'fixed',
				'bottom': '0'
			});
		} else {
			$('footer').css({
				'position': 'relative',
				'bottom': '0'
			});
		}
	});
	$(document).ready(function (e) {
		var widthDoc = $(window).width();
		placeh('.inp-vspl');
		placeh('.textarea-block');
		var isOver;
		if (widthDoc <= 800) {
			var heightTable = $('.table-all-perechen').outerHeight();
			$('.over-block').height(heightTable - 37);
			isOver = true;
		}
		if (widthDoc <= 980) {
			var heightTableO = $('.table-all-object').outerHeight();
			$('.over-block-object').height(heightTableO + 40);
			var isOverO = true;
		}
		$(window).resize(function () {
			widthDoc = $(window).width();
			if (widthDoc <= 800) {
				heightTable = $('.table-all-perechen').outerHeight();
				$('.over-block').height(heightTable - 37);
			}
			$('.one-chart-cart').each(function (i) {
				var widthLeft = $('.one-chart-cart').eq(i).find('.left-chart').width();
				var posLeft = parseInt($('.one-chart-cart').eq(i).find('.bottom-chart-cart').css('left'));
				$('.one-chart-cart').eq(i).find('.bottom-chart-cart').width(widthLeft - (posLeft * 2));
			});
			if ($('.all-object').outerWidth() >= widthDoc) {
				$(".table-all-object").mCustomScrollbar({
					axis: "x"
				});
			}
			if ($('.all-perechen').outerWidth() + 20 >= widthDoc) {
				$(".table-all-perechen").mCustomScrollbar({
					axis: "x"
				});
			}
			if (widthDoc > 1660) {
				var MarTelo = Math.round(($(window).width() - $('.telo-nac').width()) / 2 * (-1));
				setTimeout(function () {
					$('.slider-glav-big').find('#nextbutton').css('right', MarTelo)
				}, 200);
			}

		});
		$('body').scroll(function () {
			$('.hid-menu').height($(window).height());

		});

		if ($(window).width() > 1660) {
			var MarTelo = Math.round(($(window).width() - $('.telo-nac').width()) / 2 * (-1));
			setTimeout(function () {
				$('.slider-glav-big').find('#nextbutton').css('right', MarTelo)
			}, 200);
		}
		if ($('.all-object').outerWidth() >= $(window).width()) {
			$(".table-all-object").mCustomScrollbar({
				axis: "x"
			});
		}
		if ($('.all-perechen').outerWidth() + 20 >= $(window).width()) {
			$(".table-all-perechen").mCustomScrollbar({
				axis: "x"
			});
		}
		if ($('body').height() < $(window).height()) {
			$('footer').css({
				'position': 'fixed',
				'bottom': '0'
			});
		}


		var imgMenu = $('.hid-menu').children('img').attr('src');
		var bgMenu = $('.hid-menu').css('background-color');
		$('.buter-menu').click(function () {
			if ($(this).hasClass('open-menu')) {
				$(this).removeClass('open-menu');
				$(this).addClass('hov');
				$('.hid-menu').removeClass('open');
				$('.hid-menu').css('display', 'none');
				$('header').removeClass('open');
				if ($(window).width() < 500) {

					$('html').css({
						'height': 'auto'
					});
				}
				$('body').css({
					'overflow-y': 'scroll',
					'height': 'auto'
				});
				$('footer').css({
					'position': 'relative'
				});
			} else {
				$(this).addClass('open-menu');
				$('.hid-menu').addClass('open');
				$('.hid-menu').fadeIn(200);
				$(this).removeClass('hov');
				$('header').addClass('open');
				$('.hid-menu').children('img').attr('src', imgMenu);
				$('.hid-menu').css('background-color', bgMenu);
				if ($(window).width() < 500) {
					var heightHid = $('.hid-menu').outerHeight();
					var heightHead = $('header').outerHeight();
					$('html').css({
						'overflow-y': 'scroll',
						'height': heightHid
					});
				}
				$('body').css({
					'overflow-y': 'hidden',
					'height': heightHid
				});
			}
		});
		$('.buter-menu').hover(function () {
				$(this).addClass('hov');
			},
			function () {
				$(this).removeClass('hov');
			});
		$('.one-menu-h').hover(function () {
				$(this).parent('li').addClass('hov');
				var bgOneMenu = $(this).parent('li').children('div').css('background-color');
				var imgOnemenu = $(this).parent('li').find('img').attr('src');
				$('.hid-menu').css('background-color', bgOneMenu);
				$('.hid-menu').children('img').attr('src', imgOnemenu);
			},
			function () {
				$(this).parent('li').removeClass('hov');
			});
		$('.bottom-chart').each(function (ind) {
			$('.bottom-chart').eq(ind).find('.charts-block').find('.one-chart').each(function (index) {
				var planZnach = parseFloat($('.bottom-chart').eq(ind).find('.table-info-carts').find('tr').eq(index + 1).find('.plan').text()), //100 %
					faktZnach = parseFloat($('.bottom-chart').eq(ind).find('.table-info-carts').find('tr').eq(index + 1).find('.fakt').text()), //фактическое значение
					oneProc = planZnach / 100, //1%
					chastProc = Math.round(faktZnach / oneProc); //нужное кол-во %
				$(this).find('.chart').children('span').css('width', 'calc(' + chastProc + '% - 5px');
				if (Math.round(chastProc) > 6) {
					$(this).find('.chart').children('span').text(Math.round(chastProc) + '%');
				}
			});
		});
		$('.one-chart-cart').each(function (i) {
			//if($('.one-chart-cart').eq(i).hasClass('big-chart-cart'))
			//{
			if ($('.one-chart-cart').eq(i).outerHeight() == 491) {
				$('.one-chart-cart').eq(i).find('.bottom-chart-cart').css({
					'bottom': '10px',
					'transform': 'translateY(0)'
				});
				//$('.all-chart-cart').find('.one-chart-cart').eq(i).css({'min-height':'395px'});
				//$('.all-project-gl').find('.one-chart-cart').eq(i).css({'min-height':'395px'});
			}
			var widthLeft = $('.one-chart-cart').eq(i).find('.left-chart').width();
			var posLeft = parseInt($('.one-chart-cart').eq(i).find('.bottom-chart-cart').css('left'));
			$('.one-chart-cart').eq(i).find('.bottom-chart-cart').width(widthLeft - (posLeft * 2));
			var heightLeft = $('.one-chart-cart').eq(i).height();
			var heightTopLeft = $('.one-chart-cart').eq(i).find('.top-chart-cart').height();
			//$('.one-chart-cart').eq(i).find('.all-chart').height(heightLeft - heightTopLeft);
			//}
			$('.one-chart-cart').eq(i).find('.bottom-chart-cart').each(function (ind) {
				$('.bottom-chart-cart').eq(ind).find('.charts-block-cart').find('.one-chart').each(function (index) {
					var planZnachCart = parseFloat($('.one-chart-cart').eq(i).find('.bottom-chart-cart').eq(ind).find('.table-info-carts').find('tr').eq(index + 1).find('.plan').text()), //100 %
						faktZnachCart = parseFloat($('.one-chart-cart').eq(i).find('.bottom-chart-cart').eq(ind).find('.table-info-carts').find('tr').eq(index + 1).find('.fakt').text()), //фактическое значение
						oneProcCart = planZnachCart / 100, //1%
						chastProcCart = Math.round(faktZnachCart / oneProcCart); //нужное кол-во %
					$('.one-chart-cart').eq(i).find('.bottom-chart-cart').eq(ind).find('.one-chart').eq(index).find('.chart').children('span').css('width', 'calc(' + chastProcCart + '% - 5px');
					if (Math.round(chastProcCart) > 6) {
						$('.one-chart-cart').eq(i).find('.bottom-chart-cart').eq(ind).find('.one-chart').eq(index).find('.chart').children('span').text(Math.round(chastProcCart) + '%');
					}
				});
			});
		});
		$('.sorting-block').click(function () {
			if ($(this).hasClass('active')) {
				$(this).removeClass('active');
			} else {
				$(this).addClass('active');
			}
		});
		$(document).mouseup(function (e) { // событие клика по веб-документу
			var div = $('.sorting-block'); // тут указываем ID элемента
			if (!div.is(e.target) // если клик был не по нашему блоку
				&&
				div.has(e.target).length === 0) { // и не по его дочерним элементам
				div.removeClass('active'); // скрываем его
			}
		});
		$('.select-label').click(function () {
			var valSel = $(this).text();
			var spanSel = $(this).closest('.sorting-block').find('.select-title').children('div').text();
			$(this).closest('.sorting-block').attr('data-val', valSel);
			$(this).closest('.sorting-block').find('.select-title').html('<div>' + spanSel + '</div> ' + valSel);
		});
		$('.term-object').hover(function () {
				$(this).find('.hid-term').fadeIn();
			},
			function () {
				$(this).find('.hid-term').fadeOut();
			});
		$('.but-all-object').click(function () {
			var heightTable = $(this).parent().find('.all-object').height();
			$(this).parent().find('.table-all-object').css('height', heightTable);
			$('.over-block-object').height(heightTable);
			$(this).parent().find('.table-all-object').find('tr').eq(8).find('.hid-term').removeClass('end-term');
			$(this).fadeOut(300);
			$('.over-block-object').addClass('open');
		});
		if ($('.but-all-object').length) {} else {
			$('.table-all-object').css('height', 'auto');
			$('.over-block-object').css('margin-bottom', '70px');
		}
		$('.info-tab-map').click(function () {
			if ($(this).hasClass('active')) {
				$(this).removeClass('active');
				$(this).parent('.tab-map').find('.hid-info-tab').fadeOut();
			} else {
				$(this).addClass('active');
				$(this).parent('.tab-map').find('.hid-info-tab').fadeIn();
				heghtInfo = $('.big-info-tab').height();
				heightInfotab(heghtInfo);
			}
		});
		$('.one-object-tab').click(function () {
			var objectTab = $(this).text(),
				projectTab = $(this).parent().find('.one-project-tab').text();
			$('.selected-tab').children('span').text(projectTab + ', ' + objectTab);
		});

		var slideCount = $('.slide-gl').size(),
			widthSlider = $('.slider-gl').outerWidth(true),
			widthoneSlide = $('.slide-gl').outerWidth(true),
			transXnew = 0,
			maxOverSlide = Math.floor(widthSlider / widthoneSlide),
			slideNum = 0;
		var animSlide = function (arrow) {
			var widthSlider = $('.slider-gl').outerWidth(true),
				widthoneSlide = $('.slide-gl').outerWidth(true),
				maxOverSlide = Math.floor(widthSlider / widthoneSlide);
			if (arrow == "next") {
				transXnew = transXnew - widthoneSlide;
				$('.slider-track-gl').css('transition', 'all 0.6s');
				$('.slider-track-gl').css('transform', 'translateX(' + transXnew + 'px)');
				slideNum++
				$('.slider-gl').find('#prewbutton').fadeIn(200);
				$('.shadow-left').fadeIn(200);
				if (slideNum == (slideCount - maxOverSlide)) {
					$('.slider-gl').find('#nextbutton').fadeOut(200);
					$('.shadow-right').fadeOut(200);
					transXnew = transXnew + (widthSlider - (maxOverSlide * widthoneSlide)) + 20;
					$('.slider-track-gl').css('transform', 'translateX(' + transXnew + 'px)');
				}
			} else if (arrow == "prew") {
				transXnew = transXnew + widthoneSlide;
				$('.slider-track-gl').css('transition', 'all 0.6s');
				$('.slider-track-gl').css('transform', 'translateX(' + transXnew + 'px)');
				slideNum -= 1
				$('.slider-gl').find('#nextbutton').fadeIn(200);
				$('.shadow-right').fadeIn(200);
				if (slideNum == 0) {
					$('.slider-gl').find('#prewbutton').fadeOut(200);
					$('.shadow-left').fadeOut(200);
					$('.slider-track-gl').css('transform', 'translateX(0)');
				}
			}
		}
		$('.slider-gl').prepend('<div id="prewbutton">&lt;</div><div id="nextbutton">&gt;</div>');
		$('.slider-gl').find('#nextbutton').click(function () {
			animSlide("next");
			return false;
		});
		$('.slider-gl').find('#prewbutton').click(function () {
			animSlide("prew");
			return false;
		});
		$('.fon-vspl').on('click', '.inp-vspl', function () {
			var nameInp = $(this).parent('.one-inp-vspl').find('span').text();
			$(this).parent('.one-inp-vspl').children('span').fadeIn(100);
			$('.fon-vspl').on('keyup', '#mail-form', function () {
				var str = $(this).val();
				if (str.replace(/[^а-яА-ЯёЁ0-9 -]/ig, '')) {
					$(this).parent('.one-inp-vspl').find('span').text('Неверный формат');
					$(this).parent('.one-inp-vspl').addClass('dont-inp');
				} else {
					$(this).parent('.one-inp-vspl').removeClass('dont-inp');
					$(this).parent('.one-inp-vspl').find('span').text(nameInp);
				}
			});
			$('.fon-vspl').on('keyup', '#name-form', function () {
				var str = $(this).val();
				if (str.replace(/[^а-яА-ЯёЁ0-9 -]/ig, '') && str.replace(/[^a-zA-Z0-9 -]/ig, '')) {
					$(this).parent('.one-inp-vspl').find('span').text('Неверный формат');
					$(this).parent('.one-inp-vspl').addClass('dont-inp');
				} else {
					$(this).parent('.one-inp-vspl').removeClass('dont-inp');
					$(this).parent('.one-inp-vspl').find('span').text(nameInp);
				}
			});
		});
		$('.inp-vspl').focusout(function () {
			if ($(this).val() !== '') {} else {
				$(this).parent('.one-inp-vspl').children('span').css('display', 'none');
			}
		});
		$(document).mouseup(function (e) { // событие клика по веб-документу
			var div = $('.block-vspl'); // тут указываем ID элемента
			if (!div.is(e.target) // если клик был не по нашему блоку
				&&
				div.has(e.target).length === 0) { // и не по его дочерним элементам
				$('.fon-vspl').fadeOut(200); // скрываем его
			}
		});
		$('.close-vspl').click(function () {
			$('.fon-vspl').fadeOut(200);
		});
		$('.but-project').click(function () {
			if ($(this).hasClass('no_active_but')) {
				return false;
			} else {}
		});
		$(document).mouseup(function (e) { // событие клика по веб-документу
			var div = $('.block-vspl-photo, .strel-photo'); // тут указываем ID элемента
			if (!div.is(e.target) // если клик был не по нашему блоку
				&&
				div.has(e.target).length === 0) { // и не по его дочерним элементам
				$('.fon-vspl-photo').fadeOut(200); // скрываем его
			}
		});
		var indSli = 0;
		$('.block-vspl-photo').find('.strel-photo').click(function () {
			console.log('123');
			var lengImg = $('.block-vspl-photo').find('img').length;
			if ($(this).hasClass('left')) {
				if (indSli == 0) {
					indSli = lengImg
				}
				indSli -= 1;
				$('.block-vspl-photo').find('img').css('display', 'none');
				$('.block-vspl-photo').find('img').eq(indSli).fadeIn();
			} else {
				if (indSli == lengImg - 1) {
					indSli = -1
				}
				indSli++;
				$('.block-vspl-photo').find('img').css('display', 'none');
				$('.block-vspl-photo').find('img').eq(indSli).fadeIn();
			}
		});
	});


	$('.yars-chart-slider').each(function () {
		let that = $(this),
			btns = that.find('.sw-button-prev, .sw-button-next');
		let slider = new Swiper(this, {
			slidesPerView: 'auto',
			spaceBetween: 0,
			touchRatio: 1,
			resistance: true,
			resistanceRatio: 0.5,
			loop: false,
			on: {
				init: function (swiper) {
					if(swiper.virtualSize <= swiper.width) {
						btns.addClass('hidden');
					} else {
						btns.removeClass('hidden');
					}
				},
				resize: function (swiper) {
					if(swiper.virtualSize <= swiper.width) {
						btns.addClass('hidden');
					} else {
						btns.removeClass('hidden');
					}
				}
			},
			navigation: {
				nextEl: that.find('.sw-button-next')[0],
				prevEl: false
			}
		});
		$(this).on('click', '.sw-button-prev', function (e) {
			e.preventDefault();
			slider.slideTo(0);
		});
	});


});

