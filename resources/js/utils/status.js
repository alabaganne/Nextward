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

export function statusPill(status) {
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
