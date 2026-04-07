$(function ($) {
	$(document).ready(function(e) {
		$('.open_form').click(function(e){
			e.preventDefault();
			$('.fon-vspl').fadeIn(200);
		});


		$('.material-object-js').click(function(e){
			e.preventDefault();
			var rel=$(this).attr('rel');
			$('.gallery'+rel).fancybox().click();

			/*var url=$('.img_hidden[rel="'+rel+'"]').find('a').attr('href');

			$.fancybox.open(url);*/
		});

		$('.fancybox').fancybox();
	});
});

document.addEventListener('DOMContentLoaded', function() {
  const currentYear = new Date().getFullYear();

  // Ждём, когда слайдер будет готов
  setTimeout(function() {
    const slider = document.querySelector('.yars-chart-slider');
    if (!slider) return;

    const slides = slider.querySelectorAll('.swiper-slide');

    slides.forEach((slide, index) => {
      const span = slide.querySelector('span');
      if (span && parseInt(span.textContent.trim()) === currentYear) {

        span.click();

        // Вариант Б: Если нужно переключить слайд в Swiper
        if (slider.swiper) {
          slider.swiper.slideTo(index);
        }
        //slide.classList.add('swiper-slide-active');
        return false;
      }
    });
    //console.log('год:', currentYear);
    //console.log('слайдов:', slides.length);
  }, 500);
});
