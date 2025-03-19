/**
 * JavaScript para manejar los diferentes tipos de campos
 */
(function ($) {
  'use strict';

  // Objeto principal
  var SCS_FieldTypes = {
    init: function () {
      this.initTabs();
      this.initColorPicker();
      this.initMediaUploader();
      this.initDatePicker();
      this.initSelect2();
      this.initRepeaterFields();
    },

    /**
     * Inicializa las pestañas en la interfaz
     */
    initTabs: function () {
      $('.scs-tabs').tabs();
    },

    /**
     * Inicializa los selectores de color
     */
    initColorPicker: function () {
      // Si existe jQuery color picker
      if ($.fn.wpColorPicker) {
        $('.scs-color-field').wpColorPicker();
      }
    },

    /**
     * Inicializa el cargador de medios de WordPress
     */
    initMediaUploader: function () {
      // Botón para seleccionar imagen
      $(document).on('click', '.scs-upload-image-button', function (e) {
        e.preventDefault();

        var button = $(this);
        var fieldId = button.data('field-id');
        var input = $('#' + fieldId);
        var previewContainer = $('#' + fieldId + '_preview');

        // Si no existe el contenedor de vista previa, crearlo
        if (previewContainer.length === 0) {
          previewContainer = $(
            '<div id="' + fieldId + '_preview" class="scs-image-preview"></div>'
          );
          button.before(previewContainer);
        }

        // Crear frame de medios
        var frame = wp.media({
          title: 'Seleccionar Imagen',
          button: {
            text: 'Usar esta imagen',
          },
          multiple: false,
          library: {
            type: 'image',
          },
        });

        // Cuando se selecciona una imagen
        frame.on('select', function () {
          var attachment = frame.state().get('selection').first().toJSON();

          // Establecer el ID de la imagen en el campo
          input.val(attachment.id);

          // Mostrar la vista previa
          var size = 'thumbnail';
          var imgUrl =
            attachment.sizes && attachment.sizes[size]
              ? attachment.sizes[size].url
              : attachment.url;

          previewContainer.html(
            '<img src="' +
              imgUrl +
              '" alt="Vista previa de imagen" style="max-width:100px; max-height:100px;" />'
          );

          // Mostrar el botón de eliminar
          if (
            $('.scs-remove-image-button[data-field-id="' + fieldId + '"]')
              .length === 0
          ) {
            var removeButton = $(
              '<button type="button" class="button scs-remove-image-button" data-field-id="' +
                fieldId +
                '">Eliminar Imagen</button>'
            );
            button.after(' ', removeButton);
          }
        });

        // Abrir el selector de medios
        frame.open();
      });

      // Botón para eliminar imagen
      $(document).on('click', '.scs-remove-image-button', function (e) {
        e.preventDefault();

        var button = $(this);
        var fieldId = button.data('field-id');
        var input = $('#' + fieldId);
        var previewContainer = $('#' + fieldId + '_preview');

        // Limpiar el campo
        input.val('');

        // Eliminar la vista previa
        previewContainer.empty();

        // Eliminar el botón de eliminar
        button.remove();
      });
    },

    /**
     * Inicializa el selector de fechas
     */
    initDatePicker: function () {
      // Si existe jQuery UI Datepicker
      if ($.fn.datepicker) {
        $('.scs-date-field').datepicker({
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
        });
      }
    },

    /**
     * Inicializa Select2 para campos select avanzados
     */
    initSelect2: function () {
      // Si existe Select2
      if ($.fn.select2) {
        $('.scs-select2').select2({
          width: '100%',
          placeholder: 'Seleccionar...',
          allowClear: true,
        });
      }
    },

    /**
     * Inicializa los campos de tipo repeater
     */
    initRepeaterFields: function () {
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
        var template = $(
          '#' + containerId.replace('container', 'template')
        ).html();
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
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    SCS_FieldTypes.init();
  });
})(jQuery);
