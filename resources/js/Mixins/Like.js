import { router } from '@inertiajs/vue3';

export default {
    methods: {
        toggleLike(internship) {
            router.post(route('likes.store', internship.id), {}, {
                preserveScroll: true,
            });
        }
    }
};
