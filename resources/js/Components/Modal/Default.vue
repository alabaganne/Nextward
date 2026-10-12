<template>
	<span @click="toggle">
		<slot name="trigger" />
	</span>
	<transition leave-active-class="delay-300">
		<div v-show="open" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
			<div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
				<transition
					enter-active-class="ease-out duration-300"
					enter-from-class="opacity-0"
					enter-to-class="opacity-100"
					leave-active-class="ease-in duration-300"
					leave-from-class="opacity-100"
					leave-to-class="opacity-0"
				>
					<div v-show="open" @click="close" class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true"></div>
				</transition>

				<!-- This element is to trick the browser into centering the modal contents. -->
				<span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

				<transition
					enter-active-class="ease-out duration-300"
					enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					enter-to-class="opacity-100 translate-y-0 sm:scale-100"
					leave-active-class="ease-in duration-200"
					leave-from-class="opacity-100 translate-y-0 sm:scale-100"
					leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
				>
					<div
						v-show="open"
						class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:w-full"
						:class="widthClasses"
					>
						<card class="bg-white" :title="title" :subtitle="subtitle">
							<slot name="content" />
							<template v-slot:footer>
								<div class="space-x-1.5 flex justify-end">
									<button type="button" @click="close" class="btn btn-secondary">Close</button>
									<slot name="action-button" />
								</div>
							</template>
						</card>
					</div>
				</transition>
			</div>
		</div>
	</transition>
</template>

<script>
export default {
	props: {
		title: String,
		subtitle: String,
		widthClasses: {
			type: String,
			default: "sm:max-w-lg"
		}
	},
	emits: ['close'],
	data() {
		return {
			open: false
		}
	},
	methods: {
		toggle() {
			this.open = !this.open;
		},
		close() {
			this.toggle();
			this.$emit('close');
		}
	},
}
</script>
