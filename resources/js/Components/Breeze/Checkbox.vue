<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="size-4 rounded-[4px] border-slate-300 text-brand focus:border-brand"
    />
</template>

<script>
export default {
    emits: ["update:checked"],

    props: {
        checked: {
            type: [Array, Boolean],
            default: false,
        },
        value: {
            default: null,
        },
    },

    computed: {
        proxyChecked: {
            get() {
                return this.checked;
            },

            set(val) {
                this.$emit("update:checked", val);
            },
        },
    },
};
</script>
