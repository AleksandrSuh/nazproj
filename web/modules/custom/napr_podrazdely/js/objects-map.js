(function ($, Drupal) {
  Drupal.behaviors.objectsMap = {
    attach: function (context, settings) {
      if (!settings.objects_map || !settings.objects_map.points || settings.objects_map.points.length === 0) {
        console.log('Нет данных для карты');
        return;
      }

      if (window.objectsMapInitialized) {
        return;
      }

      var $mapContainer = $('#map-block', context);
      if (!$mapContainer.length) {
        console.log('Контейнер #map-block не найден');
        return;
      }

      window.objectsMapInitialized = true;

      var mapData = settings.objects_map;

      function initMap() {
        if (typeof ymaps === 'undefined') {
          console.error('Yandex Maps API не загружен');
          return;
        }

        ymaps.ready(function() {
          //$mapContainer.empty();

          var myMap = new ymaps.Map('map-block', {
            center: mapData.center,
            zoom: 10
          }, {
            searchControlProvider: 'yandex#search'
          });

          var MyIconContentLayout = ymaps.templateLayoutFactory.createClass(
            '<div style="color: #000000; font-weight: bold;">$[properties.iconContent]</div>'
          );

          mapData.points.forEach(function(point, index) {
            var balloonContent = '<div class="block-info-map">' +
              '<div class="zag-map">' + point.title + '</div>' +
              '<div class="anons-map">Срок реализации: ' + point.srok + '</div>' +
              '<div class="address-map">Адрес: ' + point.address + '</div>' +
              '</div>';

            var placemark = new ymaps.Placemark(point.coords, {
              hintContent: '',
              balloonContent: balloonContent,
              iconContent: ''
            }, {
              iconLayout: 'default#imageWithContent',
              iconImageHref: '/themes/custom/nazpro_theme/img/icon-map.png',
              iconImageSize: [30, 33],
              iconImageOffset: [-15, -16.5],
              iconContentOffset: [15, 15],
              iconContentLayout: MyIconContentLayout
            });

            placemark.events
              .add('mouseenter', function(e) {
                e.get('target').options.set('iconImageSize', [40, 42.3]);
                e.get('target').options.set('iconImageOffset', [-20, -21.15]);
              })
              .add('mouseleave', function(e) {
                e.get('target').options.set('iconImageSize', [30, 33]);
                e.get('target').options.set('iconImageOffset', [-15, -16.5]);
              });

            myMap.geoObjects.add(placemark);
          });
//          console.log('Карта создана, добавлено меток:', mapData.points.length);
        });
      }

      if (typeof ymaps === 'undefined') {
        $.getScript('https://api-maps.yandex.ru/2.1/?lang=ru_RU', function() {
          initMap();
        });
      } else {
        initMap();
      }
    }
  };
})(jQuery, Drupal);
