<template>
	<div class="min-h-screen bg-[#faf9f6] text-navy flex flex-col">
		<a href="#main" class="focus-ring sr-only z-50 rounded-lg bg-white px-4 py-2 font-semibold text-navy focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

		<header class="sticky top-0 z-40 border-b border-navy/10 bg-[#faf9f6]/95 backdrop-blur-md">
			<div class="public-container h-20 flex items-center justify-between gap-6">
				<Link href="/" class="focus-ring flex items-center rounded-lg">
					<brand-logo />
				</Link>

				<nav aria-label="Main" class="hidden md:flex items-center gap-1 text-sm font-semibold text-slate-600">
					<Link
						v-for="link in links"
						:key="link.label"
						:href="link.href"
						class="focus-ring rounded-lg px-3 py-2 transition hover:bg-pale hover:text-navy"
						:class="{ 'text-brand': link.current }"
						:aria-current="link.current ? 'page' : null"
					>
						{{ link.label }}
					</Link>
				</nav>

				<div class="hidden md:flex items-center gap-2 text-sm font-semibold">
					<template v-if="!currentUser">
						<Link :href="route('login')" class="focus-ring rounded-lg px-3 py-2 text-slate-600 transition hover:text-navy">Log in</Link>
						<Link :href="route('register')" class="cta cta-primary focus-ring px-4 py-2 text-sm">Create account</Link>
					</template>
					<Link v-else :href="route('dashboard')" class="cta cta-primary focus-ring px-4 py-2 text-sm">Dashboard</Link>
				</div>

				<button
					type="button"
					class="focus-ring md:hidden -mr-2 flex h-10 w-10 items-center justify-center rounded-lg text-navy hover:bg-pale"
					:aria-expanded="menuOpen ? 'true' : 'false'"
					aria-controls="mobile-menu"
					@click="menuOpen = !menuOpen"
				>
					<span class="sr-only">{{ menuOpen ? 'Close menu' : 'Open menu' }}</span>
					<icon :name="menuOpen ? 'x' : 'menu'" class="h-6 w-6" />
				</button>
			</div>

			<div v-show="menuOpen" id="mobile-menu" class="md:hidden border-t border-navy/5 bg-white">
				<nav aria-label="Mobile" class="px-4 py-3 flex flex-col text-base font-semibold text-navy">
					<Link
						v-for="link in links"
						:key="link.label"
						:href="link.href"
						class="focus-ring rounded-lg px-3 py-3 hover:bg-pale"
						:class="{ 'text-brand': link.current }"
						:aria-current="link.current ? 'page' : null"
					>
						{{ link.label }}
					</Link>
				</nav>
				<div class="px-4 pb-5 grid gap-2">
					<template v-if="!currentUser">
						<Link :href="route('register')" class="cta cta-primary focus-ring">Create account</Link>
						<Link :href="route('login')" class="cta cta-secondary focus-ring">Log in</Link>
					</template>
					<Link v-else :href="route('dashboard')" class="cta cta-primary focus-ring">Dashboard</Link>
				</div>
			</div>
		</header>

		<main id="main" tabindex="-1" class="flex-1 focus:outline-hidden">
			<slot />
		</main>

		<footer class="border-t border-navy/10 bg-[#faf9f6]">
			<div class="public-container py-12 grid gap-10 md:grid-cols-[1.4fr_1fr] md:items-start">
				<div class="max-w-sm">
					<Link href="/" class="focus-ring inline-flex rounded-lg">
						<brand-logo size="sm" />
					</Link>
					<p class="mt-4 text-sm leading-6 text-slate-600">Internship listings anyone can browse, and a simple way for companies to publish roles and review applications.</p>
				</div>
				<nav aria-label="Footer" class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm font-medium text-slate-600 sm:justify-self-end">
					<Link v-for="link in links" :key="link.label" :href="link.href" class="focus-ring rounded hover:text-navy">{{ link.label }}</Link>
				</nav>
			</div>
			<div class="border-t border-navy/5">
				<div class="public-container py-5 text-xs text-slate-500">
					Built by <a href="https://www.linkedin.com/in/alabaganne/" target="_blank" rel="noopener" class="focus-ring rounded font-medium text-slate-700 hover:text-brand">Ala Baganne</a>
				</div>
			</div>
		</footer>
	</div>
</template>

<script>
export default {
	data() {
		return {
			menuOpen: false,
		};
	},
	computed: {
		links() {
			return [
				{ label: 'Browse internships', href: route('internships.index'), current: route().current('internships.*') },
				{ label: 'Blog', href: route('blog.index'), current: route().current('blog.*') },
				{ label: 'About', href: route('about'), current: route().current('about') },
				{ label: 'Contact', href: route('contact'), current: route().current('contact') },
			];
		},
	},
};
</script>
