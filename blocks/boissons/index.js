(function () {
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var ServerSideRender = wp.serverSideRender;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;
	var Disabled = wp.components.Disabled;

	registerBlockType('couverty/boissons', {
		edit: function (props) {
			var attributes = props.attributes;

			return el(
				'div',
				{ className: props.className },
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __('Options d\'affichage', 'couverty'), initialOpen: true },
						el(SelectControl, {
							label: __('Disposition', 'couverty'),
							value: attributes.layout,
							options: [
								{ label: __('Liste', 'couverty'), value: 'list' },
								{ label: __('Grille', 'couverty'), value: 'grid' },
							],
							onChange: function (val) {
								props.setAttributes({ layout: val });
							},
						}),
						el(ToggleControl, {
							label: __('Afficher les prix', 'couverty'),
							checked: attributes.showPrices,
							onChange: function (val) {
								props.setAttributes({ showPrices: val });
							},
						}),
						el(ToggleControl, {
							label: __('Afficher les détails', 'couverty'),
							checked: attributes.showDetails,
							onChange: function (val) {
								props.setAttributes({ showDetails: val });
							},
						})
					)
				),
				el(
					Disabled,
					{},
					el(ServerSideRender, {
						block: 'couverty/boissons',
						attributes: attributes,
					})
				)
			);
		},
	});
})();
