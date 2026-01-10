(function (wp, blockData) {
	'use strict';

	if (!wp || !wp.blocks || !blockData) {
		return;
	}

	var registerBlockType = wp.blocks.registerBlockType;
	var createElement = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = (wp.blockEditor || wp.editor).InspectorControls;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var SelectControl = wp.components.SelectControl;

	var attributes = blockData.attributes || {};
	var formOptions = Array.isArray(blockData.formOptions) ? blockData.formOptions : [];
	var hasFormId = Object.prototype.hasOwnProperty.call(attributes, 'formId');

	function normalizeOptions(options) {
		return options.map(function (option) {
			return {
				label: option.label || '',
				value: String(option.value || '0')
			};
		});
	}

	registerBlockType('uve-mr/newsletter', {
		title: __('RelayPress', 'uve-mailrelay-newsletter'),
		description: __('Newsletter form with Turnstile + Mailrelay API + double opt-in (neutral message) and logs.', 'uve-mailrelay-newsletter'),
		icon: 'email',
		category: 'widgets',
		attributes: attributes,
		edit: function (props) {
			var attrs = props.attributes;
			var controls = [];

			if (hasFormId && formOptions.length) {
				controls.push(
					createElement(SelectControl, {
						label: __('Form', 'uve-mailrelay-newsletter'),
						value: String(attrs.formId || '0'),
						options: normalizeOptions(formOptions),
						onChange: function (value) {
							props.setAttributes({ formId: String(value) });
						}
					})
				);
			}

			controls.push(
				createElement(TextControl, {
					label: __('Title', 'uve-mailrelay-newsletter'),
					value: attrs.title || '',
					onChange: function (value) {
						props.setAttributes({ title: value });
					}
				})
			);

			controls.push(
				createElement(TextareaControl, {
					label: __('Description', 'uve-mailrelay-newsletter'),
					value: attrs.description || '',
					onChange: function (value) {
						props.setAttributes({ description: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Email placeholder', 'uve-mailrelay-newsletter'),
					value: attrs.emailPlaceholder || '',
					onChange: function (value) {
						props.setAttributes({ emailPlaceholder: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Button text', 'uve-mailrelay-newsletter'),
					value: attrs.submitLabel || '',
					onChange: function (value) {
						props.setAttributes({ submitLabel: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Group IDs', 'uve-mailrelay-newsletter'),
					value: attrs.groupIds || '',
					onChange: function (value) {
						props.setAttributes({ groupIds: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Privacy URL', 'uve-mailrelay-newsletter'),
					value: attrs.privacyUrl || '',
					onChange: function (value) {
						props.setAttributes({ privacyUrl: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Consent text', 'uve-mailrelay-newsletter'),
					value: attrs.consentLabel || '',
					onChange: function (value) {
						props.setAttributes({ consentLabel: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Extra CSS class', 'uve-mailrelay-newsletter'),
					value: attrs.extraClass || '',
					onChange: function (value) {
						props.setAttributes({ extraClass: value });
					}
				})
			);

			controls.push(
				createElement(ToggleControl, {
					label: __('Enable AJAX submissions', 'uve-mailrelay-newsletter'),
					checked: !!attrs.ajaxMode,
					onChange: function (value) {
						props.setAttributes({ ajaxMode: !!value });
					}
				})
			);

			return createElement(
				Fragment,
				null,
				createElement(InspectorControls, null, controls),
				createElement(
					'div',
					{ className: props.className },
					createElement('strong', null, attrs.title || __('RelayPress', 'uve-mailrelay-newsletter')),
					attrs.description ? createElement('p', null, attrs.description) : null
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp, window.uveMrNewsletterBlockData);
