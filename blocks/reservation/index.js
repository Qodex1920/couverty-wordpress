(function () {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var Placeholder = wp.components.Placeholder;

	registerBlockType('couverty/reservation', {
		edit: function (props) {
			var attributes = props.attributes;
			// Values offered by older versions of the block, shown the way the
			// widget renders them.
			var appearance = attributes.appearance === 'glass' || attributes.appearance === 'minimal' ? 'card' : attributes.appearance;
			var radius = attributes.radius === 'sm' ? 'md' : attributes.radius;

			return el(
				'div',
				wp.blockEditor.useBlockProps ? wp.blockEditor.useBlockProps() : { className: props.className },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Paramètres', 'couverty'), initialOpen: true },
						el(RangeControl, {
							label: __('Hauteur (px)', 'couverty'),
							value: attributes.height,
							onChange: function (val) {
								props.setAttributes({ height: val });
							},
							min: 300,
							max: 1200,
							step: 50,
						}),
						// Same choices as the Widget tab in Couverty, which applies when
						// the block sets nothing.
						el(SelectControl, {
							label: __('Style', 'couverty'),
							value: appearance,
							options: [
								{ label: __('Réglages Couverty', 'couverty'), value: '' },
								{ label: __('Clair', 'couverty'), value: 'card' },
								{ label: __('Sombre', 'couverty'), value: 'dark' },
							],
							onChange: function (val) {
								props.setAttributes({ appearance: val });
							},
						}),
						el(SelectControl, {
							label: __('Coins', 'couverty'),
							value: radius,
							options: [
								{ label: __('Réglages Couverty', 'couverty'), value: '' },
								{ label: __('Carré', 'couverty'), value: 'none' },
								{ label: __('Équilibré', 'couverty'), value: 'md' },
								{ label: __('Arrondi', 'couverty'), value: 'lg' },
							],
							onChange: function (val) {
								props.setAttributes({ radius: val });
							},
						})
					)
				),
				el(
					Placeholder,
					{
						icon: 'calendar',
						label: __('Réservation Couverty', 'couverty'),
						instructions: __(
							'Le formulaire de réservation s\'affiche sur le site public. Ajustez sa hauteur et son apparence dans les réglages du bloc.',
							'couverty'
						),
					}
				)
			);
		},
	});
})();
