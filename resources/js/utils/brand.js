// Companies without a logo get a solid colour picked from their name, so one company always looks the same.
const POOL = ['#0047ff', '#001f6d', '#2f6bff', '#047857', '#b45309', '#be185d', '#0e7490'];

export function logoColor(name) {
	if (!name) return POOL[0];
	let hash = 0;
	for (const char of name.trim().toLowerCase()) hash = (hash * 31 + char.charCodeAt(0)) % 997;
	return POOL[hash % POOL.length];
}

export function initialsFor(name) {
	if (!name) return '?';
	const word = name.trim().split(/\s+/)[0] || '';
	return word.slice(0, 1).toUpperCase() + word.slice(1, 2).toLowerCase();
}
