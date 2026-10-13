<template>
	<main-layout>
		<div class="flex justify-between items-center">
			<div class="flex items-center">
				<img
					class="h-20 w-20 rounded-full object-cover"
					src="https://images.unsplash.com/photo-1528763380143-65b3ac89a3ff?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=256&q=80"
					alt=""
				>
				<div class="ml-4">
					<div class="text-2xl font-extrabold">{{ application.student.name }}</div>
					<div class="text-sm font-medium text-gray-500">Applied for <Link :href="route('internships.show', application.internship)" class="link">{{ application.internship.title }}</Link> on {{ application.created_at }}</div>
				</div>
			</div>
			<template v-if="currentUser.userable_type === 'company' || currentUser.is_admin">
				<div v-if="application.status === null" class="flex items-center">
					<button type="button" @click="reply(false)" class="btn btn-secondary rounded-sm">Disqualify</button>
					<button type="button" @click="reply(true)" class="ml-1 btn btn-primary rounded-sm">Advance to offer</button> <!-- Schedule an interview -->
				</div>
				<div v-else class="text-sm text-gray-400 uppercase font-medium tracking-wider">
					Replied
				</div>
			</template>
			<div v-else-if="currentUser.userable_type === 'student' && application.status === null" class="flex">
				<Link class="btn btn-dark shadow-none rounded-sm" :href="route('applications.edit', application)">Edit My Application</Link>
				<delete-modal
					title="Delete My Application"
					message="Are you sure you want to delete this application? All the data related to it will be permanently removed. This action cannot be undone."
					:url="route('applications.destroy', application)"
				>
					<button type="button" class="ml-1 btn btn-danger rounded-sm">Delete</button>
				</delete-modal>
			</div>
		</div>
		<div class="mt-8 grid grid-cols-12 gap-6">
			<card class="col-span-8" title="Applicant Information" subtitle="Personal details and application.">
				<div class="">
					<dl>
						<div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
							<dt class="text-sm font-medium text-gray-500">
								Full name
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								{{ application.student.name }}
							</dd>
						</div>
						<div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-200">
							<dt class="text-sm font-medium text-gray-500">
								Email address
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								{{ application.student.email }}
							</dd>
						</div>
						<div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-200">
							<dt class="text-sm font-medium text-gray-500">
								Phone number
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								{{ application.student.phone_number || 'N/A' }}
							</dd>
						</div>
						<div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-200">
							<dt class="text-sm font-medium text-gray-500">
								Cover letter
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								{{ application.cover_letter}}
							</dd>
						</div>
						<div v-if="application.message" class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-200">
							<dt class="text-sm font-medium text-gray-500">
								Message
							</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								{{ application.message || 'N/A' }}
							</dd>
						</div>
						<div v-if="application.attachments && application.attachments.length" class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 border-t border-gray-200">
							<dt class="text-sm font-medium text-gray-500">Attachments</dt>
							<dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
								<ul class="border border-gray-200 rounded-md divide-y divide-gray-200">
									<li v-for="attachment in application.attachments" :key="attachment.url || attachment.name" class="pl-3 pr-4 py-3 flex items-center justify-between text-sm">
										<div class="w-0 flex-1 flex items-center"><icon name="paper-clip" solid class="shrink-0 h-5 w-5 text-gray-400" /><span class="ml-2 flex-1 w-0 truncate">{{ attachment.name }}</span></div>
										<div class="ml-4 shrink-0"><a :href="attachment.url" target="_blank" class="font-medium text-blue-600 hover:text-blue-500">Download</a></div>
									</li>
								</ul>
							</dd>
						</div>
					</dl>
				</div>
			</card>
			<div class="col-span-4">
				<card title="Application Status">
					<div class="p-6 font-medium" :class="{
						'text-gray-500': application.status === null,
						'text-green-500': application.status === true,
						'text-red-600': application.status === false,
					}">
						<p v-if="application.status === null">This application is under consideration.</p>
						<p v-else-if="application.status === true">Congratulations! You successfully passed the initial phase. Keep an eye on your phone and email and the company will contact you soon to schedule an interview.</p>
						<p v-else>Unfortunately, The company decided not to move forward with your application. The competition for this position was too strong.</p>
					</div>
				</card>
			</div>
		</div>
	</main-layout>
</template>

<script>
import { router } from '@inertiajs/vue3';
import MainLayout from "@/Layouts/Main.vue";
import DeleteModal from "@/Components/Modal/Delete.vue";

export default {
	components: {
		MainLayout,
		DeleteModal,
	},
	props: {
		application: {
			type: Object,
			required: true
		}
	},
	methods: {
		reply(data) {
			router.post(
				route('applications.reply', this.application),
				{ status: data }
			);
		}
	}
}
</script>
