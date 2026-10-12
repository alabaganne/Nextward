<template>
	<public-layout v-if="!currentUser">
		<section class="relative overflow-hidden bg-linear-to-br from-blue-700 via-blue-600 to-cyan-500 text-white">
			<div class="absolute inset-0 opacity-20">
				<div class="absolute -top-24 -right-20 h-72 w-72 rounded-full bg-white blur-3xl"></div>
				<div class="absolute bottom-0 left-10 h-48 w-48 rounded-full bg-cyan-200 blur-3xl"></div>
			</div>
			<div class="relative max-w-7xl mx-auto px-6 py-16 lg:py-20">
				<div class="max-w-4xl">
					<div class="inline-flex items-center px-4 py-1.5 rounded-full bg-white/15 text-white text-sm font-semibold border border-white/25 shadow-xs backdrop-blur-sm">Public internship board</div>
					<h1 class="mt-6 text-4xl md:text-6xl font-extrabold tracking-tight leading-tight text-white">Find your next internship on Internly.</h1>
					<p class="mt-6 text-lg md:text-xl text-blue-50 leading-8 max-w-3xl">Browse open internships, filter by field, city, or company, and create a student account when you're ready to apply.</p>
					<div class="mt-8 flex flex-col sm:flex-row gap-3">
						<Link :href="route('register', { account: 'student' })" class="px-5 py-3 rounded-xl bg-white text-blue-700 font-bold text-center shadow-lg hover:bg-blue-50 transition">Create student account</Link>
						<Link :href="route('login')" class="px-5 py-3 rounded-xl bg-blue-900/25 text-white font-bold text-center border border-white/25 hover:bg-blue-900/35 transition">Login</Link>
					</div>
				</div>
			</div>
		</section>
		<section class="max-w-7xl mx-auto px-6 py-8">
			<internships-content
				:internships="internships"
				:fields="fields"
				:cities="cities"
				:companies="companies"
				:filters="filters"
			/>
		</section>
	</public-layout>
	<main-layout v-else title="Internships" subtitle="View">
		<template v-slot:header-right>
			<Link
				v-if="currentUser.userable_type === 'company'"
				:href="route('internships.create')"
				class="btn btn-lg btn-primary"
			>
				Add Internship ->
			</Link>
		</template>
		<internships-content
			:internships="internships"
			:fields="fields"
			:cities="cities"
			:companies="companies"
			:filters="filters"
		/>
	</main-layout>
</template>

<script>
import MainLayout from "@/Layouts/Main.vue";
import PublicLayout from "@/Layouts/Public.vue";
import InternshipsContent from "./Content.vue";

export default {
	components: {
		MainLayout,
		PublicLayout,
		InternshipsContent,
	},
	props: {
		internships: Object,
		fields: Array,
		cities: Array,
		companies: Array,
		filters: Object,
	},
}
</script>
