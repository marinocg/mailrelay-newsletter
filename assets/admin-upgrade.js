(function() {
	var overlay = document.getElementById('relaypress-upgrade-overlay');
	if (overlay) {
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

		var closeBtn = overlay.querySelector('.relaypress-upgrade-close');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeModal);
		}

		var dismissBtn = overlay.querySelector('.relaypress-upgrade-dismiss');
		if (dismissBtn) {
			dismissBtn.addEventListener('click', closeModal);
		}

		if (overlay.getAttribute('data-auto-open') === '1') {
			openModal();
		}
	}

	var copyBtn = document.getElementById('relaypress-copy-debug');
	if (copyBtn) {
		var statusEl = document.getElementById('relaypress-copy-status');
		var successMsg = copyBtn.getAttribute('data-success') || 'Copied.';
		var failureMsg = copyBtn.getAttribute('data-failure') || 'Copy failed.';

		function setStatus(message) {
			if (statusEl) {
				statusEl.textContent = message;
			}
		}

		function fallbackCopy(text) {
			var textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.setAttribute('readonly', 'readonly');
			textarea.style.position = 'absolute';
			textarea.style.left = '-9999px';
			document.body.appendChild(textarea);
			textarea.select();
			try {
				document.execCommand('copy');
				setStatus(successMsg);
			} catch (e) {
				setStatus(failureMsg);
			}
			document.body.removeChild(textarea);
		}

		copyBtn.addEventListener('click', function() {
			var report = copyBtn.getAttribute('data-report') || '';
			if (!report) {
				setStatus(failureMsg);
				return;
			}
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(report).then(function() {
					setStatus(successMsg);
				}).catch(function() {
					fallbackCopy(report);
				});
			} else {
				fallbackCopy(report);
			}
		});
	}
})();
