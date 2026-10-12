<template>
	<div class="flex flex-col">
    <div class="overflow-x-auto">
      <div class="align-middle inline-block min-w-full p-1">
        <div class="shadow-sm overflow-hidden sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th
									v-for="field in fields"
									:key="field" scope="col"
									class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider"
									:class="[ field.toLowerCase() === 'actions' ? 'text-transparent' : 'text-gray-500' ]"
								>
                  {{ field }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
							<slot />
						</tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
	props: {
		fields: {
			type: Array,
			required: true,
		},
	},
};
</script>
