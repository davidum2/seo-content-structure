/**
 * JavaScript para campos de tipo imagen
 */
(function ($) {
  'use strict';

  /**
   * Inicializa los campos de imagen
   */
  function initImageFields() {
    // Evento para seleccionar imagen
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

    // Evento para eliminar imagen
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
  }

  /**
   * Inicialización cuando el DOM esté listo
   */
  $(function () {
    initImageFields();
  });
})(jQuery);
