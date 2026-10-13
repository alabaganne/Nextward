<template>
	<div>
		<div @click="toggle">
			<slot name="trigger" />
		</div>
		<transition
			leave-active-class="delay-500"
		> 
			<section v-show="open" class="fixed inset-0 overflow-hidden z-50" aria-labelledby="SlideOver-title" role="dialog" aria-modal="true">
				<div class="absolute inset-0 overflow-hidden">
					<transition
						enter-active-class="ease-in-out duration-500"
						enter-from-class="opacity-0"
						enter-to-class="opacity-100"
						leave-active-class="ease-in-out duration-500"
						leave-from-class="opacity-100"
						leave-to-class="opacity-0"
					>
						<div v-show="open" class="absolute inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" @click="toggle"></div>
					</transition>

					<div class="absolute inset-y-0 right-0 pl-10 max-w-full flex">
						<transition
							enter-active-class="transform transition ease-in-out duration-500"
							enter-from-class="translate-x-full"
							enter-to-class="translate-x-0"
							leave-active-class="transform transition ease-in-out duration-500"
							leave-from-class="translate-x-0"
							leave-to-class="translate-x-full"
						>
							<div v-show="open" class="relative w-screen max-w-md">
								<transition
									enter-active-class="ease-in-out duration-500"
									enter-from-class="opacity-0"
									enter-to-class="opacity-100"
									leave-active-class="ease-in-out duration-500"
									leave-from-class="opacity-100"
									leave-to-class="opacity-0"
								>
									<div v-show="open" class="absolute top-0 left-0 -ml-8 pt-4 pr-2 flex sm:-ml-10 sm:pr-4">
										<button @click="toggle" class="rounded-md text-gray-300 hover:text-white focus:outline-hidden focus:ring-2 focus:ring-white">
											<span class="sr-only">Close panel</span>
											<icon name="x" class="h-6 w-6" />
										</button>
									</div>
								</transition>

								<div class="h-full flex flex-col bg-white shadow-xl overflow-y-scroll">
									<div class="px-4 sm:px-6 py-8 bg-blue-600">
										<h2 class="text-2xl font-medium text-white" id="SlideOver-title">
											{{ title }}
										</h2>
										<p class="mt-2 text-blue-200 text-sm font-light">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
									</div>
									<div class="relative flex-1">
										<slot name="content" />
									</div>
								</div>
							</div>
						</transition>
					</div>
				</div>
			</section>
		</transition>
	</div>
</template>

<script>
export default {
	props: {
		title: String
	},
	data() {
		return {
			open: false
		}
	},
	methods: {
		toggle() {
			this.open = !this.open;
		}
	}
}
</script>