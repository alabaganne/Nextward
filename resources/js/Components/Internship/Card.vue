<template>
	<div>
		<Link :href="route('internships.show', internship)" class="block">
			<card class="transform hover:-translate-y-1 transition-transform duration-100">
				<div class="p-6" :class="{ 'lg:px-7 lg:pb-7': full }">
					<div class="flex justify-between items-start">
						<internship-header :internship="internship" :full="full" :truncateTitle="truncateTitle" />
						<div v-if="isStudent" class="shrink-0">
							<button type="button" @click.prevent="toggleLike(internship)"
								class="text-red-600 hover:bg-red-50 p-2 text-sm font-medium rounded-lg transition-colors duration-200 flex items-center"
							>
								<icon name="heart" solid class="h-5 w-5 mr-1" />
								{{ internship.liked ? 'Dislike' : 'Like' }}
							</button>
						</div>
					</div>
					<p class="mt-4 text-gray-700 leading-7">
						{{ truncate(internship.description, 350) }}
					</p>
					<div class="mt-2 flex items-end">
						<skills v-if="full" :skills="internship.skills" />
						<div v-if="isStudent && internship.application !== null" class="ml-auto text-xs text-gray-500 uppercase font-medium">
							Applied {{ internship.application.created_at }}
						</div>
					</div>
				</div>
			</card>
		</Link>
	</div>
</template>

<script>
import InternshipHeader from "@/Components/Internship/Header.vue";
import Skills from "@/Components/Skills.vue";
import Like from "@/Mixins/Like.js";

export default {
	mixins: [Like],
	components: {
		InternshipHeader,
		Skills,
	},
	props: {
		internship: Object,
		full: {
			type: Boolean,
			default: true,
		},
		truncateTitle: {
			type: Boolean,
			default: false,
		}
	},
	computed: {
		isStudent() {
			return this.currentUser && this.currentUser.userable_type === 'student';
		}
	},

}
</script>
