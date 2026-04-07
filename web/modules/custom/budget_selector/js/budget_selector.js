(function ($, Drupal, once) {
  Drupal.behaviors.budgetSelector = {
    attach: function (context, settings) {
      // Находим контейнер поля
      var $container = $(once('budget-selector', '#edit-field-budget-ids-wrapper', context));
      if (!$container.length) {
        $container = $(once('budget-selector', '.field--widget-string-textfield', context)).filter(function() {
          return $(this).find('input[name^="field_budget_ids"]').length > 0;
        });
      }

      if ($container.length) {
        // Находим само поле ввода
        var $input = $container.find('input[name^="field_budget_ids"]').first();

        if ($input.length) {
          // Добавляем кнопку
          var $button = $('<button type="button" class="button budget-open-modal" style="margin-bottom:10px;">Выбрать строки бюджета</button>');
          $container.find('.js-form-item').append($button);

          // Обработчик клика
          $button.on('click', function (e) {
            e.preventDefault();

            // Получаем текущие ID из поля (строка с запятыми)
            var currentValue = $input.val();
            var selectedIds = currentValue ? currentValue.split(',').map(function(id) {
              return id.trim();
            }).filter(function(id) {
              return id && !isNaN(id);
            }) : [];

            // Открываем окно выбора
            var url = '/admin/budget-selector?selected=' + selectedIds.join(',');
            var width = 1000;
            var height = 700;
            var left = (screen.width / 2) - (width / 2);
            var top = (screen.height / 2) - (height / 2);

            var modalWindow = window.open(url, 'budgetSelector',
              'width=' + width + ',height=' + height +
              ',top=' + top + ',left=' + left +
              ',scrollbars=yes,resizable=yes'
            );

            // Слушаем ответ
            function onMessage(event) {
              if (event.data.type === 'budgetSelected') {
                var ids = event.data.ids;
                // Записываем ID через запятую в одно поле
                $input.val(ids.join(','));

                if (modalWindow && !modalWindow.closed) {
                  modalWindow.close();
                }
                window.removeEventListener('message', onMessage);
              }
            }

            window.addEventListener('message', onMessage);
          });
        }
      }
    }
  };
})(jQuery, Drupal, once);
