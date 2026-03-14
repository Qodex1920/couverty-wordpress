(function() {
  var el = wp.element.createElement;
  var registerBlockType = wp.blocks.registerBlockType;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var NumberControl = wp.components.NumberControl;
  var SelectControl = wp.components.SelectControl;

  registerBlockType('couverty/reservation', {
    edit: function(props) {
      var attributes = props.attributes;
      return el('div', { className: props.className },
        el(InspectorControls, {},
          el(PanelBody, { title: 'Paramètres', initialOpen: true },
            el(NumberControl, {
              label: 'Hauteur (px)',
              value: attributes.height,
              onChange: function(val) { props.setAttributes({ height: val }); },
              min: 300,
              max: 1200,
              step: 50
            }),
            el(SelectControl, {
              label: 'Apparence',
              value: attributes.appearance,
              options: [
                { label: 'Card', value: 'card' },
                { label: 'Glass', value: 'glass' },
                { label: 'Minimal', value: 'minimal' },
                { label: 'Dark', value: 'dark' }
              ],
              onChange: function(val) { props.setAttributes({ appearance: val }); }
            }),
            el(SelectControl, {
              label: 'Arrondi',
              value: attributes.radius,
              options: [
                { label: 'Aucun', value: 'none' },
                { label: 'Petit', value: 'sm' },
                { label: 'Moyen', value: 'md' },
                { label: 'Grand', value: 'lg' }
              ],
              onChange: function(val) { props.setAttributes({ radius: val }); }
            })
          )
        ),
        el('div', {
          style: {
            padding: '2rem',
            background: '#f8fafc',
            border: '2px dashed #cbd5e1',
            borderRadius: '8px',
            textAlign: 'center'
          }
        },
          el('span', {
            className: 'dashicons dashicons-calendar',
            style: {
              fontSize: '2rem',
              marginBottom: '0.5rem',
              display: 'block'
            }
          }),
          el('p', {
            style: {
              fontWeight: '500',
              margin: '0.5rem 0 0.25rem'
            }
          }, 'Widget de réservation Couverty'),
          el('p', {
            style: {
              color: '#6b7280',
              fontSize: '0.875rem'
            }
          }, 'Le formulaire de réservation sera affiché ici.')
        )
      );
    }
  });
})();
