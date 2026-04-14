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

/*
(function() {
  var urlParams = new URLSearchParams(window.location.search);
  var preselectedIds = urlParams.get('selected');
  var selectedIds = preselectedIds ? preselectedIds.split(',') : [];

  function updateRowState(row, id, isSelected) {
    var link = row.querySelector('.budget-select-link');
    if (link) {
      link.textContent = isSelected ? '✓ Выбрано' : 'Выбрать';
      row.style.backgroundColor = isSelected ? '#e8f5e9' : '';
    }
  }

  document.querySelectorAll('.budget-select-link').forEach(function(link) {
    var id = link.getAttribute('data-id');
    var row = link.closest('tr');
    if (selectedIds.indexOf(id) !== -1) {
      updateRowState(row, id, true);
    }
  });

  document.addEventListener('click', function(e) {
    var link = e.target.closest('.budget-select-link');
    if (!link) return;
    e.preventDefault();

    var id = link.getAttribute('data-id');
    var row = link.closest('tr');
    var isSelected = link.textContent === '✓ Выбрано';

    if (isSelected) {
      selectedIds = selectedIds.filter(function(i) { return i != id; });
      updateRowState(row, id, false);
    } else {
      selectedIds.push(id);
      updateRowState(row, id, true);
    }
  });

  document.getElementById('budget-select-done').addEventListener('click', function() {
    if (window.opener) {
      window.opener.postMessage({ type: 'budgetSelected', ids: selectedIds }, '*');
      window.close();
    } else {
      alert('Выбрано ID: ' + selectedIds.join(', '));
    }
  });
})();

 */

document.addEventListener('DOMContentLoaded', function() {
  const currentYear = new Date().getFullYear();

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


