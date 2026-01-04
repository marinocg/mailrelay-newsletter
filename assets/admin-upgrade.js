(function() {
	var overlay = document.getElementById('uve-mr-upgrade-overlay');
	if (!overlay) return;

	function closeModal() {
		overlay.classList.remove('is-visible');
		overlay.setAttribute('aria-hidden', 'true');
	}

	function openModal() {
		overlay.classList.add('is-visible');
		overlay.setAttribute('aria-hidden', 'false');
	}

	overlay.addEventListener('click', function(ev) {
		if (ev.target === overlay) {
			closeModal();
		}
	});

	var closeBtn = overlay.querySelector('.uve-mr-upgrade-close');
	if (closeBtn) {
		closeBtn.addEventListener('click', closeModal);
	}

	var dismissBtn = overlay.querySelector('.uve-mr-upgrade-dismiss');
	if (dismissBtn) {
		dismissBtn.addEventListener('click', closeModal);
	}

	if (overlay.getAttribute('data-auto-open') === '1') {
		openModal();
	}
})();
