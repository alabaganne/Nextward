<template>
	<main-layout title="Likes" subtitle="Internships" maxWidthClass="max-w-6xl">
		<div v-if="localLikes.length > 0" class="space-y-4">
			<template v-for="internship in localLikes" :key="internship.id">
				<internship-card :internship="internship" @dislike="onDislike" />
			</template>
		</div>
		<card v-else class="p-6">
			No likes found.
		</card>
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import InternshipCard from "@/Components/Internship/Card.vue";

export default {
	components: {
		MainLayout,
		InternshipCard,
	},
	props: {
		likes: {
			type: Array,
			required: true,
		}
	},
	data() {
		return {
			localLikes: []
		}
	},
	mounted() {
		this.localLikes = JSON.parse(JSON.stringify(this.likes));
	},
	methods: {
		onDislike(internship) {
			this.localLikes = this.localLikes.filter(localInternship => localInternship.id !== internship.id)
		}
	}
}
</script>
