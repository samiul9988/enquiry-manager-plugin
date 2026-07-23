(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var target = e.target;

		if (target.classList.contains('em-delete-btn')) {
			var confirmMsg = EM_Admin.strings.confirmDelete;
			if (!confirm(confirmMsg)) {
				e.preventDefault();
			}
		}
	});
})();
