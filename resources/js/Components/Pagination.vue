<template>
  <div class="flex items-center justify-between">
    <div class="flex-1 flex justify-between sm:hidden">
      <Link
				v-if="links[0].url"
				:href="links[0].url"
				class="mr-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:text-gray-500"
			>
        <icon name="arrow-left" class="mr-2 h-4 w-4" /> Previous
      </Link>
      <Link
				v-if="links[links.length - 1].url"
				:href="links[links.length - 1].url"
				class="ml-auto relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:text-gray-500"
			>
        Next <icon name="arrow-right" class="ml-2 h-4 w-4" />
      </Link>
    </div>
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
      <div class="py-1">
        <p class="text-sm text-gray-700">
          Showing
          <span class="font-medium">{{ meta.from }}</span>
          to
          <span class="font-medium">{{ meta.to }}</span>
          of
          <span class="font-medium">{{ meta.total }}</span>
          results
        </p>
      </div>
      <div v-if="links.length > 3">
        <nav class="relative z-0 inline-flex rounded-md shadow-xs border border-gray-300 divide-x divide-gray-300 overflow-hidden -space-x-px" aria-label="Pagination">
          <template v-for="(link, i) in links" :key="i">
            <Link
							v-if="link.url && !link.active"
							:href="link.url"
							:data="routeParams"
							class="relative inline-flex items-center px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
							preserve-state
						>
							<span v-if="link.label.includes('Previous')" class="inline-flex items-center gap-2"><icon name="arrow-left" class="h-4 w-4" /> Previous</span>
							<span v-else-if="link.label.includes('Next')" class="inline-flex items-center gap-2">Next <icon name="arrow-right" class="h-4 w-4" /></span>
							<span v-else>{{ link.label }}</span>
						</Link>
            <span
							v-else
							class="relative inline-flex items-center px-4 py-2 bg-gray-100 text-sm font-medium text-gray-700"
						>
							<span v-if="link.label.includes('Previous')" class="inline-flex items-center gap-2"><icon name="arrow-left" class="h-4 w-4" /> Previous</span>
							<span v-else-if="link.label.includes('Next')" class="inline-flex items-center gap-2">Next <icon name="arrow-right" class="h-4 w-4" /></span>
							<span v-else>{{ link.label }}</span>
						</span>
          </template>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    links: {
      type: Object,
      required: true
    },
		meta: {
			type: Object,
			required: true
		},
		routeParams: {
			type: Object,
			required: false,
		}
  }
}
</script>
