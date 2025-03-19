/**
 * Scripts para la interfaz de administración del plugin
 */
(function ($) {
  'use strict';

  // Objeto principal
  var SCS_Admin = {
    init: function () {
      this.initTabs();
      this.initFieldGroups();
      this.initSchemaEditor();
      this.initTooltips();
      this.initConfirmation();
    },

    /**
     * Inicializa las pestañas en la interfaz
     */
    initTabs: function () {
      $('.scs-tabs').tabs();
    },

    /**
     * Inicializa funcionalidades para grupos de campos
     */
    initFieldGroups: function () {
      // Sortable para campos
      this.initSortableFields();

      // Añadir campo
      this.initAddField();

      // Eliminar campo
      this.initRemoveField();

      // Editar campo
      this.initEditField();

      // Añadir ubicación
      this.initAddLocation();

      // Eliminar ubicación
      this.initRemoveLocation();
    },

    /**
     * Inicializa el ordenamiento de campos
     */
    initSortableFields: function () {
      $('.scs-fields-list').sortable({
        handle: '.scs-field-sort-handle',
        placeholder: 'scs-field-placeholder',
        update: function (event, ui) {
          // Actualizar índices de los campos
          SCS_Admin.updateFieldIndexes();
        },
      });
    },

    /**
     * Actualiza los índices de los campos
     */
    updateFieldIndexes: function () {
      $('.scs-fields-list .scs-field-item').each(function (index) {
        // Actualizar atributos y nombres de campos basados en el nuevo índice
        $(this)
          .find('input, select, textarea')
          .each(function () {
            var name = $(this).attr('name');
            if (name) {
              name = name.replace(
                /\[fields\]\[\d+\]/,
                '[fields][' + index + ']'
              );
              $(this).attr('name', name);
            }
          });
      });
    },

    /**
     * Inicializa la funcionalidad para añadir campos
     */
    initAddField: function () {
      $('#scs-add-field-button').on('click', function (e) {
        e.preventDefault();

        // Obtener el template del campo
        var fieldTemplate = $('#scs-field-template').html();
        var fieldIndex = $('.scs-fields-list .scs-field-item').length;

        // Reemplazar placeholder del índice
        fieldTemplate = fieldTemplate.replace(/\{index\}/g, fieldIndex);

        // Añadir el campo a la lista
        $('.scs-fields-list').append(fieldTemplate);

        // Inicializar el nuevo campo
        SCS_Admin.initFieldSettings(
          $('.scs-fields-list .scs-field-item').last()
        );
      });
    },

    /**
     * Inicializa las configuraciones de un campo
     *
     * @param {jQuery} $field Elemento del campo
     */
    initFieldSettings: function ($field) {
      // Toggle para expandir/colapsar el campo
      $field.find('.scs-field-toggle').on('click', function () {
        $(this).closest('.scs-field-item').toggleClass('scs-field-expanded');
      });

      // Actualizar tipo de campo cuando cambia
      $field
        .find('.scs-field-type-select')
        .on('change', function () {
          var fieldType = $(this).val();
          var $fieldOptions = $(this)
            .closest('.scs-field-item')
            .find('.scs-field-options');

          // Mostrar/ocultar opciones según el tipo
          $fieldOptions.find('.scs-field-option').hide();
          $fieldOptions.find('.scs-field-option-' + fieldType).show();
        })
        .trigger('change');
    },

    /**
     * Inicializa la funcionalidad para eliminar campos
     */
    initRemoveField: function () {
      $(document).on('click', '.scs-remove-field-button', function (e) {
        e.preventDefault();

        if (confirm(scs_admin.strings.confirm_delete_field)) {
          $(this).closest('.scs-field-item').remove();
          SCS_Admin.updateFieldIndexes();
        }
      });
    },

    /**
     * Inicializa la funcionalidad para editar campos
     */
    initEditField: function () {
      $(document).on('click', '.scs-edit-field-button', function (e) {
        e.preventDefault();
        $(this).closest('.scs-field-item').toggleClass('scs-field-expanded');
      });
    },

    /**
     * Inicializa la funcionalidad para añadir ubicaciones
     */
    initAddLocation: function () {
      $('#scs-add-location-button').on('click', function (e) {
        e.preventDefault();

        // Obtener el template de ubicación
        var locationTemplate = $('#scs-location-template').html();
        var locationIndex = $('.scs-locations-list .scs-location-item').length;

        // Reemplazar placeholder del índice
        locationTemplate = locationTemplate.replace(
          /\{index\}/g,
          locationIndex
        );

        // Añadir la ubicación a la lista
        $('.scs-locations-list').append(locationTemplate);
      });
    },

    /**
     * Inicializa la funcionalidad para eliminar ubicaciones
     */
    initRemoveLocation: function () {
      $(document).on('click', '.scs-remove-location-button', function (e) {
        e.preventDefault();
        $(this).closest('.scs-location-item').remove();
      });
    },

    /**
     * Inicializa el editor de schema
     */
    initSchemaEditor: function () {
      // Si existe el editor de código
      if ($('#scs-schema-editor').length > 0) {
        // Inicializar editor de código
        var editor = wp.codeEditor.initialize($('#scs-schema-editor'), {
          codemirror: {
            mode: 'application/json',
            lineNumbers: true,
            matchBrackets: true,
            autoCloseBrackets: true,
            extraKeys: { 'Ctrl-Space': 'autocomplete' },
            theme: 'default',
            gutters: ['CodeMirror-lint-markers'],
            lint: true,
          },
        });

        // Formatear JSON al hacer clic en el botón
        $('#scs-format-json-button').on('click', function (e) {
          e.preventDefault();

          try {
            var json = editor.codemirror.getValue();
            var obj = JSON.parse(json);
            var formattedJson = JSON.stringify(obj, null, 2);
            editor.codemirror.setValue(formattedJson);
          } catch (error) {
            alert('Error al formatear JSON: ' + error.message);
          }
        });

        // Validar JSON al hacer clic en el botón
        $('#scs-validate-json-button').on('click', function (e) {
          e.preventDefault();

          try {
            var json = editor.codemirror.getValue();
            JSON.parse(json);
            alert('JSON válido');
          } catch (error) {
            alert('JSON inválido: ' + error.message);
          }
        });
      }
    },

    /**
     * Inicializa los tooltips
     */
    initTooltips: function () {
      $('.scs-tooltip').each(function () {
        var $tooltip = $(this);
        var text = $tooltip.data('tooltip');

        if (text) {
          $tooltip.append('<span class="scs-tooltip-text">' + text + '</span>');
        }
      });
    },

    /**
     * Inicializa confirmaciones para acciones destructivas
     */
    initConfirmation: function () {
      $('.scs-confirm-action').on('click', function (e) {
        var confirmMessage =
          $(this).data('confirm') || scs_admin.strings.confirm_delete;

        if (!confirm(confirmMessage)) {
          e.preventDefault();
        }
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    SCS_Admin.init();
  });
})(jQuery);
