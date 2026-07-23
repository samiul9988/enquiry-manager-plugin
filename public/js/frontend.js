(function () {
	'use strict';

	var form = document.getElementById('em-enquiry-form');
	if (!form) {
		return;
	}

	var strings = EM_Frontend.strings;

	var state = {
		submitting: false,
	};

	var elements = {
		name:    document.getElementById('em-name'),
		email:   document.getElementById('em-email'),
		phone:   document.getElementById('em-phone'),
		subject: document.getElementById('em-subject'),
		message: document.getElementById('em-message'),
		submitBtn: document.getElementById('em-submit-btn'),
		notice:  document.getElementById('em-form-notice'),
	};

	var errorEls = {
		name:    document.getElementById('em-error-name'),
		email:   document.getElementById('em-error-email'),
		phone:   document.getElementById('em-error-phone'),
		subject: document.getElementById('em-error-subject'),
		message: document.getElementById('em-error-message'),
	};

	function clearErrors() {
		Object.keys(errorEls).forEach(function (key) {
			if (errorEls[key]) {
				errorEls[key].textContent = '';
			}
		});
		Object.keys(elements).forEach(function (key) {
			var el = elements[key];
			if (el && el.classList) {
				el.classList.remove('em-input-error');
			}
		});
	}

	function showError(field, message) {
		if (errorEls[field]) {
			errorEls[field].textContent = message;
		}
		if (elements[field] && elements[field].classList) {
			elements[field].classList.add('em-input-error');
		}
	}

	function showNotice(type, message) {
		if (!elements.notice) return;
		elements.notice.style.display = 'block';
		elements.notice.className = 'em-form-notice em-notice-' + type;
		elements.notice.textContent = message;
	}

	function hideNotice() {
		if (elements.notice) {
			elements.notice.style.display = 'none';
		}
	}

	function setLoading(loading) {
		state.submitting = loading;
		if (elements.submitBtn) {
			elements.submitBtn.disabled = loading;
			if (loading) {
				elements.submitBtn.classList.add('em-loading');
				elements.submitBtn.textContent = strings.submitting;
			} else {
				elements.submitBtn.classList.remove('em-loading');
				elements.submitBtn.textContent = strings.submit;
			}
		}
	}

	function trimVal(el) {
		return el && el.value ? el.value.trim() : '';
	}

	function validate() {
		clearErrors();
		var valid = true;

		var name = trimVal(elements.name);
		var email = trimVal(elements.email);
		var phone = trimVal(elements.phone);
		var subject = trimVal(elements.subject);
		var message = trimVal(elements.message);

		if (!name) {
			showError('name', strings.nameRequired);
			valid = false;
		} else if (name.length > 255) {
			showError('name', strings.nameTooLong);
			valid = false;
		}

		if (!email) {
			showError('email', strings.emailRequired);
			valid = false;
		} else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
			showError('email', strings.emailInvalid);
			valid = false;
		} else if (email.length > 255) {
			showError('email', strings.emailTooLong);
			valid = false;
		}

		if (phone && !/^01[0-9]{9}$/.test(phone)) {
			showError('phone', strings.phoneInvalid);
			valid = false;
		}

		if (!subject) {
			showError('subject', strings.subjectRequired);
			valid = false;
		} else if (subject.length > 255) {
			showError('subject', strings.subjectTooLong);
			valid = false;
		}

		if (!message) {
			showError('message', strings.messageRequired);
			valid = false;
		} else if (message.length < 10) {
			showError('message', strings.messageTooShort);
			valid = false;
		} else if (message.length > 10000) {
			showError('message', strings.messageTooLong);
			valid = false;
		}

		return valid;
	}

	form.addEventListener('submit', function (e) {
		e.preventDefault();

		if (state.submitting) {
			return;
		}

		hideNotice();

		if (!validate()) {
			return;
		}

		setLoading(true);

		var body = new URLSearchParams();
		body.append('name', trimVal(elements.name));
		body.append('email', trimVal(elements.email));
		body.append('phone', trimVal(elements.phone));
		body.append('subject', trimVal(elements.subject));
		body.append('message', trimVal(elements.message));

		fetch(EM_Frontend.rest_url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce': EM_Frontend.nonce,
			},
			body: body,
		})
			.then(function (response) {
				return response.json().then(function (data) {
					return { status: response.status, data: data };
				});
			})
			.then(function (result) {
				setLoading(false);

				if (result.data.success) {
					showNotice('success', result.data.message || strings.successMessage);
					form.reset();
					clearErrors();
				} else {
					showNotice('error', result.data.message || strings.genericError);
				}
			})
			.catch(function () {
				setLoading(false);
				showNotice('error', strings.genericError);
			});
	});
})();
