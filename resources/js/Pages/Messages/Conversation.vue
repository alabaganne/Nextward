<template>
	<div class="flex-1 max-h-full flex flex-col">
		<div class="h-20 px-6 bg-white flex justify-between items-center border-b">
			<template v-if="contact">
				<div class="flex items-center">
					<img
						class="h-12 w-12 object-cover rounded-full"
						src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=4&w=200&h=200&q=60"
						alt=""
					>
					<div class="ml-3">
						<div class="leading-5 text-lg font-medium">{{ contact.name }}</div>
						<div class="text-gray-500 text-sm">{{ contact.email }}</div>
					</div>
				</div>
				<!-- <div></div> -->
			</template>
			<div v-else>No contact selected</div>
		</div>
		<div v-if="contact" class="flex-1 p-6 space-y-4 overflow-auto" scroll-region ref="messagesContainer">
			<div class="flex" v-for="message in contact.messages" :key="message.id">
				<div class="flex flex-col" :class="{ 'ml-auto text-right': sentMessage(message	) }">
					<div class="px-1 mb-0.5 text-gray-900 text-xs font-medium">
						{{ sentMessage(message) ? 'You' : contact.name }}
					</div>
					<card class="p-4 max-w-lg shadow-none border">
						<div class="text-sm text-left">
							{{ message.text }}
						</div>
						<div class="mt-1 text-xs text-blue-500 font-medium">{{ message.created_at }}</div>
					</card>
				</div>
			</div>
		</div>
		<form v-if="contact" @submit.prevent="sendMessage" class="border-t bg-white mt-auto pl-2 pr-6 py-3 mb-0 flex items-center">
			<input
				v-model="newMessage"
				type="text"
				class="text-sm bg-transparent border-none focus:placeholder-transparent focus:ring-0 w-full"
				placeholder="Aa"
			/>
			<button type="submit" class="ml-4 shrink-0 btn btn-primary">Send -></button>
		</form>
	</div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import { nextTick } from 'vue';

export default {
	props: {
		contact: {
			type: Object,
			required: false,
		}
	},
	data() {
		return {
			newMessage: ''
		}
	},
	watch: {
		contact: {
			handler() {
				this.scrollToBottom();
			},
			deep: true
		}
	},
	mounted() {
		this.scrollToBottom();
	},
	methods: {
		sendMessage() {
			if(this.newMessage === '') return;

			router.post(
				this.route('messages.store'),
				{ to_id: this.contact.id, text: this.newMessage },
				{ preserveScroll: true, onSuccess: () => { this.newMessage = '' } },
			);
		},
		async scrollToBottom() {
			await nextTick();
			this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight - this.$refs.messagesContainer.clientHeight;
		},
		sentMessage(message) {
			return this.currentUser.id === message.from_id;
		},
		formatDate(date) {
			return `Today at ${date.toLocaleString('en-US', { timeZone: 'UTC', hour12: true, hour: 'numeric', minute: 'numeric' })}`;
		}
	}
}
</script>
