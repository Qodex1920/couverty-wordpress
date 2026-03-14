(function() {
  var el = wp.element.createElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var ServerSideRender = wp.serverSideRender;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var ToggleControl = wp.components.ToggleControl;
  var SelectControl = wp.components.SelectControl;

  registerBlockType('couverty/menu', {
    edit: function(props) {
      var attributes = props.attributes;
      return el('div', { className: props.className },
        el(InspectorControls, {},
          el(PanelBody, { title: 'Options d\'affichage', initialOpen: true },
            el(SelectControl, {
              label: 'Disposition',
              value: attributes.layout,
              options: [
                { label: 'Liste', value: 'list' },
                { label: 'Grille', value: 'grid' }
              ],
              onChange: function(val) { props.setAttributes({ layout: val }); }
            }),
            el(ToggleControl, {
              label: 'Afficher les prix',
              checked: attributes.showPrices,
              onChange: function(val) { props.setAttributes({ showPrices: val }); }
            }),
            el(ToggleControl, {
              label: 'Afficher les images',
              checked: attributes.showImages,
              onChange: function(val) { props.setAttributes({ showImages: val }); }
            }),
            el(ToggleControl, {
              label: 'Afficher les allergènes',
              checked: attributes.showAllergens,
              onChange: function(val) { props.setAttributes({ showAllergens: val }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'couverty/menu',
          attributes: attributes
        })
      );
    }
  });
})();
