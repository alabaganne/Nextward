// Application status → pill colours (design/DESIGN.md §2 status table).
// Status colours stay literal hex — they're outside the brand ramp.

const PILLS = {
	submitted: { label: 'Submitted', text: '#5b6677', bg: '#eef2f9', dot: '#8a93a3' },
	viewed: { label: 'Viewed', text: '#1d4ed8', bg: '#eef4ff', dot: '#2563eb' },
	review: { label: 'In review', text: '#b45309', bg: '#fef3c7', dot: '#d97706' },
	interview: { label: 'Interview', text: '#047857', bg: '#dcfce7', dot: '#059669' },
	offer: { label: 'Offer', text: '#6d28d9', bg: '#ede9fe', dot: '#7c3aed' },
	closed: { label: 'Closed', text: '#5b6677', bg: '#eef2f9', dot: '#8a93a3' },

	// Legacy statuses from the pre-redesign schema.
	accepted: { label: 'Accepted', text: '#047857', bg: '#dcfce7', dot: '#059669' },
	rejected: { label: 'Closed', text: '#5b6677', bg: '#eef2f9', dot: '#8a93a3' },
};

const NEUTRAL = { text: '#5b6677', bg: '#eef2f9', dot: '#8a93a3' };
const SUCCESS = { text: '#047857', bg: '#dcfce7', dot: '#059669' };
const WARNING = { text: '#b45309', bg: '#fef3c7', dot: '#d97706' };
const INFO = { text: '#1d4ed8', bg: '#eef4ff', dot: '#2563eb' };
const DANGER = { text: '#dc2626', bg: '#fef2f2', dot: '#dc2626' };

// Import items, sources, permissions and runs on the admin Imports pages.
const IMPORT_PILLS = {
	pending: { label: 'Not read yet', ...NEUTRAL },
	review: { label: 'Waiting for review', ...WARNING },
	published: { label: 'Published', ...SUCCESS },
	closed: { label: 'Closed', ...NEUTRAL },
	rejected: { label: 'Rejected', ...DANGER },
	approved: { label: 'Approved', ...SUCCESS },
	revoked: { label: 'Revoked', ...DANGER },
	enabled: { label: 'Enabled', ...INFO },
	paused: { label: 'Paused', ...NEUTRAL },
	running: { label: 'Running', ...INFO },
	completed: { label: 'Completed', ...SUCCESS },
	partial: { label: 'Partial', ...WARNING },
	failed: { label: 'Failed', ...DANGER },
	suppressed: { label: 'Hidden', ...WARNING },
	open: { label: 'Open', ...INFO },
};

export function statusPill(status, set = 'application') {
	if (set === 'import') {
		const key = String(status || '').toLowerCase();
		return IMPORT_PILLS[key] || { label: key.charAt(0).toUpperCase() + key.slice(1), ...NEUTRAL };
	}

	// Legacy schema stored a boolean (true = accepted, null = pending).
	if (status === true) return PILLS.accepted;
	if (status === false) return PILLS.closed;
	if (!status) return PILLS.submitted;

	const key = String(status).toLowerCase();
	return (
		PILLS[key] || {
			label: key.charAt(0).toUpperCase() + key.slice(1),
			text: '#5b6677',
			bg: '#eef2f9',
			dot: '#8a93a3',
		}
	);
}
