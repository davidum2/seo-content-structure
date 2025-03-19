/**
 * JavaScript para campos de tipo repeater
 */
(function ($) {
  'use strict';

  /**
   * Inicializa los campos repeater
   */
  function initRepeaterFields() {
    // Hacer que las filas sean ordenables
    $('.scs-repeater-rows').sortable({
      handle: '.scs-repeater-sort-handle',
      placeholder: 'scs-repeater-row-placeholder',
      forcePlaceholderSize: true,
      update: function (event, ui) {
        updateRepeaterValues($(this).closest('.scs-repeater-container'));
      },
    });

    // Evento para añadir una nueva fila
    $(document).on('click', '.scs-repeater-add-row', function () {
      addRepeaterRow($(this).data('target'));
      return false;
    });

    // Evento para eliminar una fila
    $(document).on('click', '.scs-repeater-remove-row', function () {
      removeRepeaterRow($(this).closest('.scs-repeater-row'));
      return false;
    });

    // Evento para actualizar el valor cuando cambia un subcampo
    $(document).on('change keyup', '.scs-repeater-sub-field', function () {
      var container = $(this).closest('.scs-repeater-container');
      updateRepeaterValues(container);
    });
  }

  /**
   * Añade una nueva fila al repeater
   *
   * @param {string} containerId ID del contenedor del repeater
   */
  function addRepeaterRow(containerId) {
    var container = $('#' + containerId);
    var maxRows = parseInt(container.data('max-rows'), 10);
    var rowCount = container.find('.scs-repeater-row').length;

    // Verificar si se ha alcanzado el máximo de filas
    if (maxRows > 0 && rowCount >= maxRows) {
      alert('Se ha alcanzado el número máximo de filas permitidas.');
      return;
    }

    // Obtener la plantilla
    var template = $('#' + containerId.replace('container', 'template')).html();
    var newIndex = rowCount;

    // Reemplazar el índice en la plantilla
    var newRow = template.replace(/\{\{index\}\}/g, newIndex);

    // Añadir la nueva fila
    container.find('.scs-repeater-rows').append(newRow);

    // Actualizar los valores
    updateRepeaterValues(container);
  }

  /**
   * Elimina una fila del repeater
   *
   * @param {jQuery} row Elemento de la fila a eliminar
   */
  function removeRepeaterRow(row) {
    var container = row.closest('.scs-repeater-container');
    var minRows = parseInt(container.data('min-rows'), 10);
    var rowCount = container.find('.scs-repeater-row').length;

    // Verificar si se ha alcanzado el mínimo de filas
    if (minRows > 0 && rowCount <= minRows) {
      alert('Se requiere un mínimo de ' + minRows + ' filas.');
      return;
    }

    // Eliminar la fila
    row.remove();

    // Actualizar los valores
    updateRepeaterValues(container);
  }

  /**
   * Actualiza el valor del campo oculto con los valores actuales
   *
   * @param {jQuery} container Contenedor del repeater
   */
  function updateRepeaterValues(container) {
    var fieldId = container.data('field-id');
    var values = [];

    // Recopilar valores de cada fila
    container.find('.scs-repeater-row').each(function () {
      var row = {};

      // Recopilar valores de cada subcampo en esta fila
      $(this)
        .find('.scs-repeater-sub-field')
        .each(function () {
          var name = $(this).attr('name');
          var keyMatch = name.match(/\[([^\]]+)\](?:\[\])?$/);

          if (keyMatch) {
            var key = keyMatch[1];
            row[key] = $(this).val();
          }
        });

      values.push(row);
    });

    // Actualizar el campo oculto con el JSON
    $('#' + fieldId).val(JSON.stringify(values));
  }

  /**
   * Inicialización cuando el DOM esté listo
   */
  $(function () {
    initRepeaterFields();
  });
})(jQuery);
