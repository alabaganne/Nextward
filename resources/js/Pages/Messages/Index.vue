<template>
	<div class="flex-1 flex flex-row max-h-full overflow-y-auto" style="max-height: calc(100vh - 80px);">
		<contacts-list
			:contacts="contacts"
			:selectedContactId="selectedContact?.id"
		/>
		<conversation :contact="selectedContact" />
	</div>
</template>

<script>
import { router } from '@inertiajs/vue3';
import ContactsList from "./ContactsList.vue";
import Conversation from "./Conversation.vue";

export default {
	components: { Conversation, ContactsList },
	props: {
		contacts: Array,
		selectedContact: Object,
	},
	mounted() {
		for(let i = 0; i < this.contacts.length; i++) {
			if(this.contacts[i].id === this.route().params.user_id) {
				this.selectedContact = this.contacts[i];
				break;
			}
		}

		if (!window.Echo) return;

		window.Echo.private('user.' + this.currentUser.id)
			.listen('.messages', message => {
				console.log(message);
				if(route().current('messages.index')) {
					router.reload();
				}
			});
	},
}
</script>
