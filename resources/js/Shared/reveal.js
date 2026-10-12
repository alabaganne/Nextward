// v-reveal: fades an element in the first time it scrolls into view.
// Usage: v-reveal or v-reveal="120" (delay in ms). Does nothing for reduced motion or without IntersectionObserver.
let observer;

function getObserver() {
	if (!observer) {
		observer = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					observer.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px' });
	}

	return observer;
}

export default {
	mounted(el, binding) {
		if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			return;
		}

		if (binding.value) {
			el.style.setProperty('--reveal-delay', `${binding.value}ms`);
		}

		el.classList.add('reveal');
		getObserver().observe(el);
	},
	unmounted(el) {
		observer?.unobserve(el);
	},
};
