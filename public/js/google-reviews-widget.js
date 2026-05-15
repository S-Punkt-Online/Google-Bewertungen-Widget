document.addEventListener('DOMContentLoaded', () => {
	const reviewContent = document.querySelector('.rsce_google_review');
	const closeButton = document.querySelector('.rsce_google_review__close');

	if (!reviewContent || !closeButton) {
		return;
	}

	const closeIcon = closeButton.querySelector('.icon-close');
	const googleIcon = closeButton.querySelector('.icon-google');

	const updateState = (closed) => {
		reviewContent.classList.toggle('google_hidden', closed);
		localStorage.setItem('googleReviewClosed', closed.toString());

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

	const isClosed = localStorage.getItem('googleReviewClosed') === 'true';

	updateState(isClosed);

	closeButton.addEventListener('click', () => {
		const newState = !reviewContent.classList.contains('google_hidden');
		updateState(newState);
	});
});