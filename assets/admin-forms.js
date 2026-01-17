(function() {
	var tabs = document.querySelectorAll('.relaypress-tabs a');
	var panels = document.querySelectorAll('.relaypress-tab-panel');
	if (tabs.length && panels.length) {
		function activateTab(targetId) {
			tabs.forEach(function(tab) {
				var isActive = tab.getAttribute('href') === targetId;
				tab.classList.toggle('nav-tab-active', isActive);
			});
			panels.forEach(function(panel) {
				panel.classList.toggle('is-active', '#' + panel.id === targetId);
			});
		}

		tabs.forEach(function(tab) {
			tab.addEventListener('click', function(ev) {
				ev.preventDefault();
				var targetId = tab.getAttribute('href');
				if (targetId) {
					activateTab(targetId);
				}
			});
		});

		activateTab('#relaypress-tab-fields');
	}

	function toggleGroup(checkboxSelector, rowSelector) {
		var checkbox = document.querySelector(checkboxSelector);
		if (!checkbox) return;
		var rows = document.querySelectorAll(rowSelector);
		function update() {
			var hide = checkbox.checked;
			rows.forEach(function(row) {
				row.style.display = hide ? 'none' : '';
			});
		}
		checkbox.addEventListener('change', update);
		update();
	}

	function toggleSelectValue(selectSelector, rowSelector, showValue) {
		var select = document.querySelector(selectSelector);
		if (!select) return;
		var rows = document.querySelectorAll(rowSelector);
		function update() {
			var show = select.value === showValue;
			rows.forEach(function(row) {
				row.style.display = show ? '' : 'none';
			});
		}
		select.addEventListener('change', update);
		update();
	}

	toggleGroup('input[name="form_config[consent][inherit]"]', '.relaypress-consent-fields');
	toggleGroup('input[name="form_config[rate_limit][inherit]"]', '.relaypress-rate-limit-fields');
	toggleSelectValue('#relaypress-locale-mode', '.relaypress-locale-force-row', 'force');

	var available = document.getElementById('relaypress-groups-available');
	var selected = document.getElementById('relaypress-groups-selected');
	var hiddenInput = document.getElementById('relaypress-group-ids');
	var addBtn = document.getElementById('relaypress-group-add');
	var removeBtn = document.getElementById('relaypress-group-remove');

	function moveSelected(from, to) {
		var opts = Array.from(from.options).filter(function(opt) { return opt.selected; });
		opts.forEach(function(opt) {
			opt.selected = false;
			to.appendChild(opt);
		});
		updateHidden();
	}

	function updateHidden() {
		if (!hiddenInput || !selected) return;
		var ids = Array.from(selected.options).map(function(opt) { return opt.value; });
		hiddenInput.value = ids.join(',');
	}

	if (addBtn && removeBtn && available && selected) {
		addBtn.addEventListener('click', function() { moveSelected(available, selected); });
		removeBtn.addEventListener('click', function() { moveSelected(selected, available); });
		available.addEventListener('dblclick', function() { moveSelected(available, selected); });
		selected.addEventListener('dblclick', function() { moveSelected(selected, available); });
		updateHidden();
	}
})();
