(function ($, Drupal) {
  Drupal.behaviors.objectsSort = {
    attach: function (context, settings) {
      // Инициализируем только один раз
      if (window.objectsSortInitialized) {
        return;
      }
      window.objectsSortInitialized = true;

      // Функция сортировки таблицы
      function sortTable(sortBy) {
        var $tbody = $('.all-object tbody');
        var rows = $tbody.find('tr.object-row').toArray();

        if (rows.length === 0) {
          return;
        }

        rows.sort(function(a, b) {
          if (sortBy === 'name') {
            var aVal = $(a).find('.name-object span').text();
            var bVal = $(b).find('.name-object span').text();
            return aVal.localeCompare(bVal, 'ru');
          }

          if (sortBy === 'price') {
            var aVal = parseFloat($(a).find('.price-object').text().replace(/\s/g, '').replace(',', '.')) || 0;
            var bVal = parseFloat($(b).find('.price-object').text().replace(/\s/g, '').replace(',', '.')) || 0;
            return aVal - bVal;
          }

          if (sortBy === 'srok') {
            var aVal = $(a).find('.term-object span').text();
            var bVal = $(b).find('.term-object span').text();
            return aVal.localeCompare(bVal);
          }

          return 0;
        });

        $tbody.empty().append(rows);
      }

      // Слушаем клики по .select-label и сортируем
      $(document).on('click', '.select-label.sort', function() {
        var sortBy = $(this).attr('rel');
        sortTable(sortBy);
      });
    }
  };
})(jQuery, Drupal);
