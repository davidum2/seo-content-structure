(function ($) {
  'use strict';

  // Objeto principal
  var SCS_PostTypes = {
    init: function () {
      this.initTabs();
      this.initSortable();
      this.initDashiconSelector();
      this.initTaxonomyManager();
      this.initSlugGenerator();
    },

    /**
     * Inicializa las pestañas
     */
    initTabs: function () {
      $('.scs-tabs').tabs();
    },

    /**
     * Inicializa elementos arrastrables
     */
    initSortable: function () {
      $('.scs-sortable').sortable({
        placeholder: 'scs-sortable-placeholder',
        handle: '.scs-sort-handle',
      });
    },

    /**
     * Inicializa el selector de dashicons
     */
    initDashiconSelector: function () {
      $('#show-dashicons').on('click', function () {
        $('.scs-dashicons-selector').slideToggle();
      });

      $(document).on('click', '.dashicon-item', function () {
        var icon = $(this).data('icon');
        $('#menu_icon').val(icon);
        $('.scs-dashicons-selector').slideUp();
      });
    },

    /**
     * Inicializa el administrador de taxonomías
     */
    initTaxonomyManager: function () {
      var taxonomyIndex = $('.scs-taxonomy-item').length;

      // Añadir nueva taxonomía
      $('#scs-add-taxonomy-button').on('click', function () {
        var template = $('#scs-taxonomy-template').html();
        var newTaxonomy = template.replace(/{index}/g, taxonomyIndex);
        $('#scs-taxonomies-container').append(newTaxonomy);
        taxonomyIndex++;
      });

      // Eliminar taxonomía
      $(document).on('click', '.remove-taxonomy', function () {
        $(this).closest('.scs-taxonomy-item').remove();
      });
    },

    /**
     * Inicializa la generación automática de slugs
     */
    initSlugGenerator: function () {
      $('#singular_name').on('blur', function () {
        var $postType = $('#post_type');
        if ($postType.val() === '' || !$postType.prop('readonly')) {
          var slug = $(this)
            .val()
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-');
          $postType.val(slug);
        }
      });
    },
  };

  // Inicializar cuando el DOM esté listo
  $(function () {
    SCS_PostTypes.init();
  });
})(jQuery);
