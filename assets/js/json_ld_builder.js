/**
 * JavaScript para el constructor de JSON-LD
 */
(function ($) {
  'use strict';

  // Objeto principal
  var SCS_JsonLdBuilder = {
    // Elementos del DOM
    $form: null,
    $editor: null,
    $output: null,
    $typeSelect: null,
    $propertiesContainer: null,

    // Datos actuales
    currentType: '',
    schema: {},

    /**
     * Inicializa el constructor
     */
    init: function () {
      // Obtener elementos del DOM
      this.$form = $('#scs-json-ld-form');
      this.$editor = $('#scs-schema-editor');
      this.$output = $('#scs-schema-output');
      this.$typeSelect = $('#scs-schema-type');
      this.$propertiesContainer = $('#scs-schema-properties');

      // Inicializar el editor de código
      this.initEditor();

      // Inicializar eventos
      this.initEvents();
    },

    /**
     * Inicializa el editor de código
     */
    initEditor: function () {
      if (this.$editor.length && typeof wp !== 'undefined' && wp.codeEditor) {
        this.editor = wp.codeEditor.initialize(this.$editor, {
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

        // Establecer valor inicial
        this.editor.codemirror.setValue(JSON.stringify(this.schema, null, 2));
      }
    },

    /**
     * Inicializa los eventos
     */
    initEvents: function () {
      var self = this;

      // Cambio de tipo de schema
      this.$typeSelect.on('change', function () {
        self.currentType = $(this).val();
        self.loadSchemaProperties();
      });

      // Generar JSON-LD
      $('#scs-generate-schema').on('click', function (e) {
        e.preventDefault();
        self.generateSchema();
      });

      // Formatear JSON
      $('#scs-format-json').on('click', function (e) {
        e.preventDefault();
        self.formatJson();
      });

      // Validar JSON
      $('#scs-validate-json').on('click', function (e) {
        e.preventDefault();
        self.validateJson();
      });

      // Cargar JSON desde el editor
      $('#scs-load-from-editor').on('click', function (e) {
        e.preventDefault();
        self.loadFromEditor();
      });
    },

    /**
     * Carga las propiedades del schema seleccionado
     */
    loadSchemaProperties: function () {
      var self = this;

      if (!this.currentType) {
        this.$propertiesContainer.html(
          '<p>Selecciona un tipo de schema primero.</p>'
        );
        return;
      }

      // Mostrar indicador de carga
      this.$propertiesContainer.html('<p>Cargando propiedades...</p>');

      // Realizar solicitud AJAX para obtener propiedades
      $.ajax({
        url: ajaxurl,
        type: 'GET',
        data: {
          action: 'scs_get_schema_properties',
          type: this.currentType,
          nonce: scs_admin.nonce,
        },
        success: function (response) {
          if (response.success) {
            self.renderProperties(response.data);
          } else {
            self.$propertiesContainer.html(
              '<p class="error">Error: ' + response.data + '</p>'
            );
          }
        },
        error: function () {
          self.$propertiesContainer.html(
            '<p class="error">Error al cargar propiedades.</p>'
          );
        },
      });
    },

    /**
     * Renderiza los campos de las propiedades
     *
     * @param {Object} properties Propiedades a renderizar
     */
    renderProperties: function (properties) {
      var self = this;

      if (!properties || $.isEmptyObject(properties)) {
        this.$propertiesContainer.html(
          '<p>No hay propiedades disponibles para este tipo.</p>'
        );
        return;
      }

      var html = '<div class="scs-schema-properties-list">';

      // Ordenar propiedades (requeridas primero)
      var sortedProperties = [];
      var requiredProperties = [];
      var optionalProperties = [];

      $.each(properties, function (key, property) {
        if (property.required) {
          requiredProperties.push({ key: key, property: property });
        } else {
          optionalProperties.push({ key: key, property: property });
        }
      });

      sortedProperties = requiredProperties.concat(optionalProperties);

      // Renderizar cada propiedad
      $.each(sortedProperties, function (index, item) {
        var key = item.key;
        var property = item.property;

        html += self.renderPropertyField(key, property);
      });

      html += '</div>';

      this.$propertiesContainer.html(html);

      // Inicializar campos especiales
      this.initSpecialFields();
    },

    /**
     * Renderiza un campo para una propiedad
     *
     * @param {string} key Clave de la propiedad
     * @param {Object} property Configuración de la propiedad
     * @return {string} HTML del campo
     */
    renderPropertyField: function (key, property) {
      var html = '<div class="scs-schema-property">';
      var fieldId = 'scs-property-' + key;
      var fieldName = 'scs_schema[' + key + ']';
      var required = property.required ? ' required' : '';
      var value = this.schema[key] || '';

      // Etiqueta del campo
      html += '<label for="' + fieldId + '">' + property.label;
      if (property.required) {
        html += ' <span class="required">*</span>';
      }
      html += '</label>';

      // Descripción si existe
      if (property.description) {
        html += '<p class="description">' + property.description + '</p>';
      }

      // Renderizar campo según el tipo
      switch (property.type) {
        case 'text':
          html +=
            '<input type="text" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '"' +
            required +
            ' class="regular-text">';
          break;

        case 'textarea':
          html +=
            '<textarea id="' +
            fieldId +
            '" name="' +
            fieldName +
            '"' +
            required +
            ' rows="4" class="large-text">' +
            value +
            '</textarea>';
          break;

        case 'number':
          html +=
            '<input type="number" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '"' +
            required +
            ' class="small-text">';
          break;

        case 'select':
          html +=
            '<select id="' +
            fieldId +
            '" name="' +
            fieldName +
            '"' +
            required +
            '>';
          html += '<option value="">- Seleccionar -</option>';

          if (property.options) {
            $.each(property.options, function (optionValue, optionLabel) {
              var selected = value === optionValue ? ' selected' : '';
              html +=
                '<option value="' +
                optionValue +
                '"' +
                selected +
                '>' +
                optionLabel +
                '</option>';
            });
          }

          html += '</select>';
          break;

        case 'url':
          html +=
            '<input type="url" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '"' +
            required +
            ' class="regular-text">';
          break;

        case 'image':
          html += '<div class="scs-image-field">';
          html +=
            '<input type="hidden" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '">';
          html +=
            '<div id="' +
            fieldId +
            '_preview" class="scs-image-preview"></div>';
          html +=
            '<button type="button" class="button scs-upload-image-button" data-field-id="' +
            fieldId +
            '">Seleccionar Imagen</button>';

          if (value) {
            html +=
              ' <button type="button" class="button scs-remove-image-button" data-field-id="' +
              fieldId +
              '">Eliminar Imagen</button>';
          }

          html += '</div>';
          break;

        case 'date':
          html +=
            '<input type="date" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '"' +
            required +
            ' class="regular-text">';
          break;

        case 'object':
          html += '<div class="scs-object-field">';
          html += '<div class="scs-object-properties">';

          if (property.properties) {
            $.each(property.properties, function (propKey, propConfig) {
              var subFieldId = fieldId + '_' + propKey;
              var subFieldName = fieldName + '[' + propKey + ']';
              var subRequired = propConfig.required ? ' required' : '';
              var subValue = value && value[propKey] ? value[propKey] : '';

              html += '<div class="scs-object-property">';
              html += '<label for="' + subFieldId + '">' + propConfig.label;
              if (propConfig.required) {
                html += ' <span class="required">*</span>';
              }
              html += '</label>';

              html +=
                '<input type="text" id="' +
                subFieldId +
                '" name="' +
                subFieldName +
                '" value="' +
                subValue +
                '"' +
                subRequired +
                ' class="regular-text">';
              html += '</div>';
            });
          }

          html += '</div>'; // .scs-object-properties
          html += '</div>'; // .scs-object-field
          break;

        default:
          html +=
            '<input type="text" id="' +
            fieldId +
            '" name="' +
            fieldName +
            '" value="' +
            value +
            '"' +
            required +
            ' class="regular-text">';
      }

      html += '</div>'; // .scs-schema-property

      return html;
    },

    /**
     * Inicializa campos especiales como cargadores de imágenes
     */
    initSpecialFields: function () {
      // Inicializar datepicker si existe
      if ($.fn.datepicker) {
        this.$propertiesContainer.find('input[type="date"]').datepicker({
          dateFormat: 'yy-mm-dd',
          changeMonth: true,
          changeYear: true,
        });
      }
    },

    /**
     * Genera el schema JSON-LD a partir del formulario
     */
    generateSchema: function () {
      // Inicializar el objeto schema
      this.schema = {
        '@context': 'https://schema.org',
        '@type': this.currentType,
      };

      // Recopilar valores del formulario
      var formData = this.$form.serializeArray();

      $.each(
        formData,
        function (i, field) {
          var name = field.name;
          var value = field.value;

          // Si es un campo de esquema
          if (name.startsWith('scs_schema[')) {
            // Extraer la clave de propiedad
            var matches = name.match(/scs_schema\[([^\]]+)\]/);

            if (matches && matches[1]) {
              var key = matches[1];

              // Verificar si es una propiedad de objeto
              var objMatches = name.match(/scs_schema\[([^\]]+)\]\[([^\]]+)\]/);

              if (objMatches && objMatches[1] && objMatches[2]) {
                var objKey = objMatches[1];
                var propKey = objMatches[2];

                // Inicializar objeto si no existe
                if (!this.schema[objKey]) {
                  this.schema[objKey] = {};
                }

                // Establecer la propiedad del objeto
                this.schema[objKey][propKey] = value;
              } else {
                // Propiedad simple
                this.schema[key] = value;
              }
            }
          }
        }.bind(this)
      );

      // Actualizar el editor
      if (this.editor) {
        this.editor.codemirror.setValue(JSON.stringify(this.schema, null, 2));
      }

      // Actualizar salida preformateada si existe
      if (this.$output.length) {
        this.$output.text(JSON.stringify(this.schema, null, 2));
      }
    },

    /**
     * Formatea el JSON en el editor
     */
    formatJson: function () {
      if (this.editor) {
        try {
          var jsonText = this.editor.codemirror.getValue();
          var jsonObj = JSON.parse(jsonText);
          var formattedJson = JSON.stringify(jsonObj, null, 2);
          this.editor.codemirror.setValue(formattedJson);
        } catch (error) {
          alert('Error al formatear JSON: ' + error.message);
        }
      }
    },

    /**
     * Valida el JSON en el editor
     */
    validateJson: function () {
      if (this.editor) {
        try {
          var jsonText = this.editor.codemirror.getValue();
          JSON.parse(jsonText);

          // Validar contra backend
          this.validateWithBackend(jsonText);
        } catch (error) {
          alert('JSON inválido: ' + error.message);
        }
      }
    },

    /**
     * Valida el JSON con el backend
     *
     * @param {string} jsonText JSON a validar
     */
    validateWithBackend: function (jsonText) {
      var self = this;
      var jsonObj = JSON.parse(jsonText);

      if (!jsonObj['@type']) {
        alert('El JSON debe tener una propiedad @type');
        return;
      }

      // Realizar solicitud AJAX para validar
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'scs_validate_schema',
          nonce: scs_admin.nonce,
          type: jsonObj['@type'],
          schema: jsonObj,
        },
        success: function (response) {
          if (response.success) {
            alert('Schema JSON-LD válido');
          } else {
            alert('Error de validación: ' + response.data.message);
          }
        },
        error: function () {
          alert('Error al validar el schema.');
        },
      });
    },

    /**
     * Carga los datos desde el editor hacia el formulario
     */
    loadFromEditor: function () {
      if (this.editor) {
        try {
          var jsonText = this.editor.codemirror.getValue();
          var jsonObj = JSON.parse(jsonText);

          // Verificar que tenga tipo
          if (!jsonObj['@type']) {
            alert('El JSON debe tener una propiedad @type');
            return;
          }

          // Establecer el tipo en el select
          var schemaType = jsonObj['@type'];
          this.$typeSelect.val(schemaType).trigger('change');

          // Guardar los datos para llenar el formulario cuando se carguen las propiedades
          this.schema = jsonObj;

          // Actualizar mensaje
          alert(
            'JSON cargado correctamente. Ahora puedes editar los campos del formulario.'
          );
        } catch (error) {
          alert('JSON inválido: ' + error.message);
        }
      }
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    // Solo inicializar si estamos en la página del constructor
    if ($('#scs-json-ld-builder').length) {
      SCS_JsonLdBuilder.init();
    }
  });
})(jQuery);
