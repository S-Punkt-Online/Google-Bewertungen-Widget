document.addEventListener('DOMContentLoaded', () => {
	// Speicherzugriff absichern: localStorage kann fehlen oder Fehler werfen
	// (Safari Private Mode, blockierte Websitedaten, Quota). Dann wird der
	// Zustand einfach nicht gespeichert, das Widget funktioniert trotzdem.
	const readStorage = (key) => {
		try {
			return window.localStorage.getItem(key);
		} catch (e) {
			return null;
		}
	};

	const writeStorage = (key, value) => {
		try {
			window.localStorage.setItem(key, value);
		} catch (e) {
			// Zustand wird nicht persistiert
		}
	};

	document.querySelectorAll('.rsce_google_review').forEach((reviewContent) => {
		const closeButton = reviewContent.querySelector('.rsce_google_review__close');

		if (!closeButton) {
			return;
		}

		const closeIcon = closeButton.querySelector('.icon-close');
		const googleIcon = closeButton.querySelector('.icon-google');

		const updateState = (closed) => {
			reviewContent.classList.toggle('google_hidden', closed);
			writeStorage('googleReviewClosed', closed.toString());

			if (closeIcon) {
				closeIcon.hidden = closed;
			}

			if (googleIcon) {
				googleIcon.hidden = !closed;
			}

			closeButton.setAttribute(
				'aria-label',
				closed ? 'Google Bewertungen öffnen' : 'Google Bewertungen schließen'
			);
		};

		const isClosed = readStorage('googleReviewClosed') === 'true';

		updateState(isClosed);

		closeButton.addEventListener('click', () => {
			const newState = !reviewContent.classList.contains('google_hidden');
			updateState(newState);
		});
	});
});
