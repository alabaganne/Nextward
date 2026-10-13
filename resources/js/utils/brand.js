// Company avatar helpers (design/DESIGN.md §2). Companies get a 140°
// gradient picked per-company and kept consistent across screens. Known
// demo companies use their canonical gradient; anything else hashes into
// the same pool so a company always renders the same colour.

const CANONICAL = {
	'atlas cloud': 'linear-gradient(140deg,#3b82f6,#1d4ed8)',
	novabyte: 'linear-gradient(140deg,#6366f1,#4338ca)',
	greenfield: 'linear-gradient(140deg,#10b981,#047857)',
	'stride labs': 'linear-gradient(140deg,#f59e0b,#d97706)',
	lumen: 'linear-gradient(140deg,#ec4899,#be185d)',
	vertex: 'linear-gradient(140deg,#06b6d4,#0e7490)',
	realinflo: 'linear-gradient(140deg,#0ea5e9,#0369a1)',
};

const POOL = [
	'linear-gradient(140deg,#3b82f6,#1d4ed8)',
	'linear-gradient(140deg,#6366f1,#4338ca)',
	'linear-gradient(140deg,#10b981,#047857)',
	'linear-gradient(140deg,#f59e0b,#d97706)',
	'linear-gradient(140deg,#ec4899,#be185d)',
	'linear-gradient(140deg,#06b6d4,#0e7490)',
	'linear-gradient(140deg,#0ea5e9,#0369a1)',
];

export function gradientFor(name) {
	if (!name) return POOL[0];
	const key = name.trim().toLowerCase();
	if (CANONICAL[key]) return CANONICAL[key];

	let hash = 0;
	for (const char of key) hash = (hash * 31 + char.charCodeAt(0)) % 997;
	return POOL[hash % POOL.length];
}

export function initialsFor(name) {
	if (!name) return '?';
	const word = name.trim().split(/\s+/)[0] || '';
	return word.slice(0, 1).toUpperCase() + word.slice(1, 2).toLowerCase();
}
