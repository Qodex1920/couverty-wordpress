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

	registerBlockType('couverty/menu-du-jour', {
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
							label: __('Contenu', 'couverty'),
							value: attributes.part,
							options: [
								{ label: __('Tout le menu', 'couverty'), value: 'all' },
								{ label: __('Entrées seulement', 'couverty'), value: 'entrees' },
								{ label: __('Plats seulement', 'couverty'), value: 'plats' },
								{ label: __('Desserts seulement', 'couverty'), value: 'desserts' },
							],
							onChange: function (val) {
								props.setAttributes({ part: val });
							},
						}),
						el(ToggleControl, {
							label: __('Aujourd\'hui seulement', 'couverty'),
							checked: attributes.day === 'today',
							onChange: function (val) {
								props.setAttributes({ day: val ? 'today' : 'all' });
							},
						}),
						el(ToggleControl, {
							label: __('Afficher le prix', 'couverty'),
							checked: attributes.showPrice,
							onChange: function (val) {
								props.setAttributes({ showPrice: val });
							},
						})
					)
				),
				el(
					Disabled,
					{},
					el(ServerSideRender, {
						block: 'couverty/menu-du-jour',
						attributes: attributes,
					})
				)
			);
		},
	});
})();
