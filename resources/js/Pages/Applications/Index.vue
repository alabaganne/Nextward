<template>
	<main-layout title="Applications" subtitle="View" maxWidthClass="max-w-6xl">
		<div v-if="applications.data.length > 0">
			<card class="divide-y">
				<template v-for="application in applications.data" :key="application.id">
					<application-card :application="application" />
				</template>
			</card>
			<pagination :links="applications.meta.links" :meta="applications.meta" class="mt-4" />
		</div>
		<card v-else class="p-6">
			No applications found.
		</card>
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import ApplicationCard from "@/Components/Application/Card.vue";
import Pagination from '@/Components/Pagination.vue';

export default {
	components: {
		MainLayout,
		ApplicationCard,
		Pagination,
	},
	props: {
		applications: Object,
	}
}
</script>
