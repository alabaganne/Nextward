<template>
	<div class="w-96 shrink-0 bg-white border-r flex flex-col">
		<div class="shrink-0">
			<div class="px-4 h-20 flex items-center border-b">
				<div>
					<h3 class="text-lg leading-6 font-medium text-gray-900">Messages</h3>
					<p class="max-w-2xl text-sm font-medium text-gray-500">{{ contacts.length }} conversations</p>
				</div>
			</div>
			<div class="px-4 py-3 bg-gray-50 text-gray-500 font-medium text-sm flex items-center">
				<icon name="sort-descending" class="h-5 w-5 mr-2 text-gray-400" />
				Sorted by date
			</div>
		</div>
		<div class="border-t flex-1 overflow-y-auto" scroll-region>
			<template v-if="contacts.length > 0">
				<Link
					class="text-left hover:bg-gray-50 p-4 flex items-start w-full border-b"
					v-for="contact in contacts"
					:key="contact.id"
					:href="route('messages.index', { user_id: contact.id })"
					:class="{
						'bg-gray-50': contact.id === selectedContactId
					}"
					preserve-scroll
				>
					<div>
						<div class="font-medium">{{ contact.name }}</div>
						<p class="text-gray-500 text-sm">{{ truncate(contact.latest_message.text, 85) }}</p>
						<span class="mt-1 text-xs text-blue-500 font-medium">{{ contact.latest_message.created_at }}</span>
					</div>
				</Link>
			</template>
			<div v-else class="p-4 text-sm text-gray-700">No messages found</div>
		</div>
	</div>
</template>

<script>
export default {
	emits: ['select-contact'],
	props: {
		contacts: {
			type: Array,
			required: true,
		},
		selectedContactId: Number,
	}
}
</script>
