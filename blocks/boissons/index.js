(function() {
  var el = wp.element.createElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var ServerSideRender = wp.serverSideRender;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var ToggleControl = wp.components.ToggleControl;
  var SelectControl = wp.components.SelectControl;

  registerBlockType('couverty/boissons', {
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
              label: 'Afficher les détails',
              checked: attributes.showDetails,
              onChange: function(val) { props.setAttributes({ showDetails: val }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'couverty/boissons',
          attributes: attributes
        })
      );
    }
  });
})();
