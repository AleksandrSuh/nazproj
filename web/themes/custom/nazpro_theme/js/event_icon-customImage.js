ymaps.ready(init);

function init() {
  const myMap = new ymaps.Map('map-block', {
    center: [56.842770, 60.807811],
    zoom: 10
  }, {
    searchControlProvider: 'yandex#search'
  }),
        MyIconContentLayout = ymaps.templateLayoutFactory.createClass('<div style="color: #000000; font-weight: bold;">$[properties.iconContent]</div>'),
       myPlacemark = new ymaps.Placemark([56.844718, 60.591633], {
            hintContent: '',
            balloonContent: '<div class="block-info-map"><div class="zag-map">Наименование объекта капитального строительства</div><div class="anons-map">Срок реализации: 31 Декабря 2021</div><div class="address-map">Адрес: г. Екатеринбург, ул Проспект Космонавтов 111</div></div>',
            iconContent: ''
        }, {
            // Опции.
            // Необходимо указать данный тип макета.
            iconLayout: 'default#imageWithContent',
            // Своё изображение иконки метки.
            iconImageHref: 'img/icon-map.png',
            // Размеры метки.
            iconImageSize: [30, 33],
            // Смещение левого верхнего угла иконки относительно
            // её "ножки" (точки привязки).
            iconImageOffset: [-15, -16.5],
            // Смещение слоя с содержимым относительно слоя с картинкой.
            iconContentOffset: [15, 15],
            // Макет содержимого.
            iconContentLayout: MyIconContentLayout
        }),
		myPlacemarkTwo = new ymaps.Placemark([56.891851, 60.599754], {
            hintContent: '',
            balloonContent: '<div class="block-info-map"><div class="zag-map">Наименование объекта капитального строительства</div><div class="anons-map">Срок реализации: 31 Декабря 2021</div><div class="address-map">Адрес: г. Екатеринбург, ул Проспект Космонавтов 111</div></div>',
            iconContent: ''
        }, {
            // Опции.
            // Необходимо указать данный тип макета.
            iconLayout: 'default#imageWithContent',
            // Своё изображение иконки метки.
            iconImageHref: 'img/icon-map.png',
            // Размеры метки.
            iconImageSize: [30, 33],
            // Смещение левого верхнего угла иконки относительно
            // её "ножки" (точки привязки).
            iconImageOffset: [-15, -16.5],
            // Смещение слоя с содержимым относительно слоя с картинкой.
            iconContentOffset: [15, 15],
            // Макет содержимого.
            iconContentLayout: MyIconContentLayout,
			//при клике не исчезает метка
			hideIconOnBalloonOpen:false,
			balloonOffset: [0, -21.15]
        });
  myMap.geoObjects.add(myPlacemark)
		.add(myPlacemarkTwo);
  
  
  
  
  
  myPlacemark.events
    .add('mouseenter', function (e) {
	  e.get('target').options.set('iconImageSize', [40, 42.3]);
      e.get('target').options.set('iconImageOffset', [-20, -21.15]);
  })
    
    .add('mouseleave', function (e) {
      e.get('target').options.set('iconImageSize', [30, 33]);
      e.get('target').options.set('iconImageOffset',  [-15, -16.5]);
  });
  myPlacemarkTwo.events
    .add('mouseenter', function (e) {
	  e.get('target').options.set('iconImageSize', [40, 42.3]);
      e.get('target').options.set('iconImageOffset', [-20, -21.15]);
  })
    
    .add('mouseleave', function (e) {
      e.get('target').options.set('iconImageSize', [30, 33]);
      e.get('target').options.set('iconImageOffset',  [-15, -16.5]);
  });
  
}