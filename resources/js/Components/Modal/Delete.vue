<template>
	<modal>
		<template v-slot:trigger>
			<slot />
		</template>
		<template v-slot:content>
			<div class="px-4 pt-6 pb-5">
				<div class="sm:flex sm:items-start">
          <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
            <!-- Heroicon name: outline/exclamation -->
            <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
            <h3 class="text-lg leading-6 font-medium text-gray-900 capitalize">
              {{ title }}
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">
								{{ message }}
              </p>
            </div>
          </div>
        </div>
			</div>
		</template>
		<template v-slot:action-button>
			<button @click="onDelete" type="button" class="btn btn-danger">Delete</button>
		</template>
	</modal>
</template>

<script>
import { router } from '@inertiajs/vue3';
import Modal from "./Default.vue";

export default {
	components: { Modal },
	props: {
		title: {
			type: String,
			required: true,
		},
		message: {
			type: String,
			required: true
		},
		url: {
			type: String,
			required: true,
		},
	},
	methods: {
		onDelete() {
			router.delete(this.url);
		}
	}
}
</script>