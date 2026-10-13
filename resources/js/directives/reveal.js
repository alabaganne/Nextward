// Reveal-on-scroll (design/INTEGRATION.md §5). Use `v-reveal` where the
// prototype had class="reveal". Always degrades to visible.
export default {
	mounted(el) {
		el.classList.add('opacity-0', 'translate-y-[22px]', 'transition-all', 'duration-700');
		el.style.transitionTimingFunction = 'cubic-bezier(.2,.7,.2,1)';

		const show = () => {
			el.style.opacity = '1';
			el.style.transform = 'none';
		};

		if (!('IntersectionObserver' in window)) return show();

		// Safety net from the prototype: never leave content hidden.
		setTimeout(show, 1200);

		const io = new IntersectionObserver(
			(entries) =>
				entries.forEach((entry) => {
					if (entry.isIntersecting) {
						show();
						io.unobserve(el);
					}
				}),
			{ threshold: 0.08, rootMargin: '0px 0px -5% 0px' }
		);
		io.observe(el);
	},
};
