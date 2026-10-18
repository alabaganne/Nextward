import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const KEY = 'nextward.activity';
const LIMIT = 500;
// owner is the user id the data belongs to, null for guest data
const state = reactive({ ready: false, owner: null, views: {}, saves: [] });
let currentUser = null;

const isCandidate = (user) => !!user && (user.is_candidate || user.userable_type === 'candidate');
const asObject = (value) => (value && typeof value === 'object' && !Array.isArray(value) ? value : {});

function load() {
    try {
        const data = JSON.parse(window.localStorage.getItem(KEY) || '{}');
        state.owner = data.owner ?? null;
        state.views = asObject(data.views);
        state.saves = Array.isArray(data.saves) ? data.saves.map(Number) : [];
    } catch {
        // storage blocked or bad data, start empty
    }
}

function persist() {
    state.views = Object.fromEntries(Object.entries(state.views).sort((a, b) => String(b[1]).localeCompare(String(a[1]))).slice(0, LIMIT));
    state.saves = state.saves.slice(-LIMIT);
    try {
        window.localStorage.setItem(KEY, JSON.stringify({ owner: state.owner, views: state.views, saves: state.saves }));
    } catch {
        // storage full or blocked
    }
}

function reset(owner = null) {
    state.owner = owner;
    state.views = {};
    state.saves = [];
    persist();
}

async function sync(user) {
    const guestData = state.owner === null;
    if (!guestData && state.owner !== user.id) reset(user.id);
    try {
        const { data } = await axios.post('/activity/sync', { views: state.views, saves: guestData ? state.saves : [] });
        if (currentUser?.id !== user.id) return;
        state.owner = user.id;
        state.views = asObject(data.views);
        state.saves = (data.saves || []).map(Number);
        persist();
        if (data.saves_added > 0) router.reload();
    } catch {
        // kept locally, sent again on the next page load
    }
}

export function startActivitySync(page) {
    if (typeof window === 'undefined') return;
    load();
    state.ready = true;
    watch(() => page.props.auth?.user?.id ?? null, () => {
        currentUser = page.props.auth?.user || null;
        if (!currentUser) {
            if (state.owner !== null) reset();
        } else if (isCandidate(currentUser)) {
            sync(currentUser);
        }
    }, { immediate: true });
}

export function markOpened(opportunity) {
    if (typeof window === 'undefined') return;
    state.views[opportunity.id] = new Date().toISOString();
    persist();
    if (isCandidate(currentUser)) axios.post(`/opportunities/${opportunity.id}/opened`).catch(() => {});
}

export function toggleSaved(opportunity) {
    const id = Number(opportunity.id);
    const setSaved = (saved) => {
        state.saves = saved ? [...state.saves.filter((item) => item !== id), id] : state.saves.filter((item) => item !== id);
        persist();
    };
    if (!currentUser) return setSaved(!state.saves.includes(id));
    const wasSaved = !!opportunity.liked;
    router.post(`/like/${id}`, {}, { preserveScroll: true, onSuccess: () => setSaved(!wasSaved) });
}

export const openedAt = (id) => (state.ready && state.views[id]) || null;
export const isSaved = (id) => state.ready && state.saves.includes(Number(id));
