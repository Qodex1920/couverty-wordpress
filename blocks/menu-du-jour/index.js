(function() {
  var el = wp.element.createElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var ServerSideRender = wp.serverSideRender;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var ToggleControl = wp.components.ToggleControl;

  registerBlockType('couverty/menu-du-jour', {
    edit: function(props) {
      var attributes = props.attributes;
      return el('div', { className: props.className },
        el(InspectorControls, {},
          el(PanelBody, { title: 'Options d\'affichage', initialOpen: true },
            el(ToggleControl, {
              label: 'Afficher le prix',
              checked: attributes.showPrice,
              onChange: function(val) { props.setAttributes({ showPrice: val }); }
            })
          )
        ),
        el(ServerSideRender, {
          block: 'couverty/menu-du-jour',
          attributes: attributes
        })
      );
    }
  });
})();
