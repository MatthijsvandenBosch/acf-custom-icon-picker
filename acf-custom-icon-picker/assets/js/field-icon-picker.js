// assets/js/field-icon-picker.js
(function () {
	document.addEventListener('click', function (e) {
		const opt = e.target.closest('.acf-custom-icon-picker .icon-option');
		if (!opt) return;

		const picker = opt.closest('.acf-custom-icon-picker');
		const input = picker.querySelector('input[type="hidden"]');

		// Als het geselecteerde icon opnieuw wordt geklikt, deselecteer dan
		if (opt.classList.contains('selected')) {
			opt.classList.remove('selected');
			input.value = '';
		} else {
			// Anders, selecteer het nieuwe icon
			picker
				.querySelectorAll('.icon-option')
				.forEach((o) => o.classList.remove('selected'));
			opt.classList.add('selected');
			input.value = opt.getAttribute('data-value'); // altijd de slug
		}

		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
})();
