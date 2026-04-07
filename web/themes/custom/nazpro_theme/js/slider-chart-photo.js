
$(function ($) {
var hwSlideSpeed = 700;
var hwTimeOut = 3000;
var hwNeedLinks;


$(document).ready(function(e) {
	
	
	var slideNum = 0;
	$('.big-chart-cart').each(function(ind){
		
	
		var slideCount = $('.big-chart-cart').eq(ind).find('.slide-chart-photo').size(),
			widthSlider = $('.big-chart-cart').eq(ind).find('.slider-chart-photo').outerWidth(true),
			widthoneSlide = $('.big-chart-cart').eq(ind).find('.slide-chart-photo').outerWidth(true),
			transXnew = 0,
			maxOverSlide = Math.floor(widthSlider/widthoneSlide);
		if(slideCount > maxOverSlide){hwNeedLinks = true;}else{hwNeedLinks = false;}
		var animSlide = function(arrow){
			var widthSlider = $('.big-chart-cart').eq(ind).find('.slider-chart-photo').outerWidth(true),
				widthoneSlide = $('.big-chart-cart').eq(ind).find('.slide-chart-photo').outerWidth(true),
				maxOverSlide = Math.floor(widthSlider/widthoneSlide);
			if(arrow == "next"){
				transXnew = transXnew - widthoneSlide;
				$('.big-chart-cart').eq(ind).find('.slider-track').css('transition', 'all 0.6s');
				$('.big-chart-cart').eq(ind).find('.slider-track').css('transform', 'translateX('+transXnew+'px)');
				slideNum++
				$('.big-chart-cart').eq(ind).find('#prewbutton').fadeIn(200);
				if(slideNum == (slideCount-maxOverSlide))
					{
					$('.big-chart-cart').eq(ind).find('#nextbutton').fadeOut(200);
					transXnew = transXnew + (widthSlider - (maxOverSlide*widthoneSlide)) + 20;
					$('.big-chart-cart').eq(ind).find('.slider-track').css('transform', 'translateX('+transXnew+'px)');
					}
			}
			else if(arrow == "prew")
				{
				transXnew = transXnew + widthoneSlide;
				$('.big-chart-cart').eq(ind).find('.slider-track').css('transition', 'all 0.6s');
				$('.big-chart-cart').eq(ind).find('.slider-track').css('transform', 'translateX('+transXnew+'px)');
				slideNum-=1
				$('.big-chart-cart').eq(ind).find('#nextbutton').fadeIn(200);
				if(slideNum == 0)
					{
					$('.big-chart-cart').eq(ind).find('#prewbutton').fadeOut(200);
					$('.big-chart-cart').eq(ind).find('.slider-track').css('transform', 'translateX(0)');
					}
				}
			//$('.slide-chart-photo').eq(slideNum).fadeIn(hwSlideSpeed, rotator);
			}
		if(hwNeedLinks){
		
			$('.big-chart-cart').eq(ind).find('.slider-chart-photo').prepend('<a id="prewbutton" href="#">&lt;</a><a id="nextbutton" href="#">&gt;</a>');
			$('#nextbutton').click(function(){
				animSlide("next");
				return false;
				})
			$('#prewbutton').click(function(){
				animSlide("prew");
				return false;
				})
		}
	});
});
});