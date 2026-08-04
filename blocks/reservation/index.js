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
						el(SelectControl, {
							label: __('Apparence', 'couverty'),
							value: attributes.appearance,
							options: [
								{ label: __('Carte', 'couverty'), value: 'card' },
								{ label: __('Verre', 'couverty'), value: 'glass' },
								{ label: __('Minimal', 'couverty'), value: 'minimal' },
								{ label: __('Sombre', 'couverty'), value: 'dark' },
							],
							onChange: function (val) {
								props.setAttributes({ appearance: val });
							},
						}),
						el(SelectControl, {
							label: __('Arrondi', 'couverty'),
							value: attributes.radius,
							options: [
								{ label: __('Aucun', 'couverty'), value: 'none' },
								{ label: __('Petit', 'couverty'), value: 'sm' },
								{ label: __('Moyen', 'couverty'), value: 'md' },
								{ label: __('Grand', 'couverty'), value: 'lg' },
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
