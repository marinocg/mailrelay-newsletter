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
	var formEmptyMessage = blockData.formEmptyMessage || '';
	var formCreateLabel = blockData.formCreateLabel || '';
	var formCreateUrl = blockData.formCreateUrl || '';
	var hasFormId = Object.prototype.hasOwnProperty.call(attributes, 'formId');

	function normalizeOptions(options) {
		return options.map(function (option) {
			return {
				label: option.label || '',
				value: String(option.value || '0')
			};
		});
	}

	registerBlockType('relaypress/newsletter', {
		title: __('RelayPress', 'relaypress-newsletter'),
		description: __('Newsletter form with Turnstile + Mailrelay API + double opt-in (neutral message) and logs.', 'relaypress-newsletter'),
		icon: 'email',
		category: 'widgets',
		attributes: attributes,
		edit: function (props) {
			var attrs = props.attributes;
			var controls = [];

			if (hasFormId && formOptions.length) {
				controls.push(
					createElement(SelectControl, {
						label: __('Form', 'relaypress-newsletter'),
						value: String(attrs.formId || ''),
						options: normalizeOptions(formOptions),
						onChange: function (value) {
							props.setAttributes({ formId: String(value) });
						}
					})
				);
			} else if (hasFormId && formEmptyMessage && formCreateUrl) {
				controls.push(
					createElement(
						'p',
						null,
						formEmptyMessage + ' ',
						createElement(
							'a',
							{ href: formCreateUrl, target: '_blank', rel: 'noopener noreferrer' },
							formCreateLabel || __('Create a form', 'relaypress-newsletter')
						)
					)
				);
			}

			controls.push(
				createElement(TextControl, {
					label: __('Title', 'relaypress-newsletter'),
					value: attrs.title || '',
					onChange: function (value) {
						props.setAttributes({ title: value });
					}
				})
			);

			controls.push(
				createElement(TextareaControl, {
					label: __('Description', 'relaypress-newsletter'),
					value: attrs.description || '',
					onChange: function (value) {
						props.setAttributes({ description: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Email placeholder', 'relaypress-newsletter'),
					value: attrs.emailPlaceholder || '',
					onChange: function (value) {
						props.setAttributes({ emailPlaceholder: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Button text', 'relaypress-newsletter'),
					value: attrs.submitLabel || '',
					onChange: function (value) {
						props.setAttributes({ submitLabel: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Group IDs', 'relaypress-newsletter'),
					value: attrs.groupIds || '',
					onChange: function (value) {
						props.setAttributes({ groupIds: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Privacy URL', 'relaypress-newsletter'),
					value: attrs.privacyUrl || '',
					onChange: function (value) {
						props.setAttributes({ privacyUrl: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Consent text', 'relaypress-newsletter'),
					value: attrs.consentLabel || '',
					onChange: function (value) {
						props.setAttributes({ consentLabel: value });
					}
				})
			);

			controls.push(
				createElement(TextControl, {
					label: __('Extra CSS class', 'relaypress-newsletter'),
					value: attrs.extraClass || '',
					onChange: function (value) {
						props.setAttributes({ extraClass: value });
					}
				})
			);

			controls.push(
				createElement(ToggleControl, {
					label: __('Enable AJAX submissions', 'relaypress-newsletter'),
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
					createElement('strong', null, attrs.title || __('RelayPress', 'relaypress-newsletter')),
					attrs.description ? createElement('p', null, attrs.description) : null
				)
			);
		},
		save: function () {
			return null;
		}
	});
})(window.wp, window.relaypressNewsletterBlockData);
