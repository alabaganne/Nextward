// Word copy of a CV, built in the browser (from Resume Studio's export). Plain and editable, in the theme's accent and paper size.
const PAGE = { letter: { width: 12240, height: 15840 }, a4: { width: 11906, height: 16838 } };

const monthYear = (value, locale) => {
	const match = /^(\d{4})-(\d{2})$/.exec(String(value || '').trim());
	if (!match) return String(value || '').trim();
	return new Intl.DateTimeFormat(locale, { month: 'short', year: 'numeric' }).format(new Date(Number(match[1]), Number(match[2]) - 1, 1));
};

export const dateRange = (row, labels, locale) => {
	const from = monthYear(row.start, locale);
	const to = row.current ? labels.present : monthYear(row.end, locale);
	return from && to ? `${from} — ${to}` : from || to;
};

const toUrl = (text) => (/^https?:\/\//i.test(text) ? text : `https://${text}`);
const isLink = (text) => /^(https?:\/\/)?([\w-]+\.)+[a-z]{2,}(\/\S*)?$/i.test(text) && !text.includes('@');

export async function downloadCvDocx({ cv, labels, locale = 'en', accent = '2A4B7C', paper = 'a4', fileName = 'Resume' }) {
	const { Document, Packer, Paragraph, TextRun, ExternalHyperlink, AlignmentType, BorderStyle, TabStopType } = await import('docx');
	const ink = '15171A';
	const soft = '555555';
	const margin = 720;
	const page = PAGE[paper] || PAGE.a4;
	const rightTab = page.width - margin * 2;
	const children = [];

	children.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 60 }, children: [new TextRun({ text: cv.name || '', bold: true, size: 40, color: ink })] }));
	if (cv.headline) children.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 80 }, children: [new TextRun({ text: cv.headline, size: 22, color: accent, italics: true })] }));

	const contact = [];
	(cv.contact || []).filter((line) => line.trim()).forEach((line) => {
		if (contact.length) contact.push(new TextRun({ text: '   ·   ', size: 18, color: 'AAAAAA' }));
		const link = line.includes('@') && !line.includes(' ') ? `mailto:${line}` : isLink(line) ? toUrl(line) : null;
		const run = new TextRun({ text: line.replace(/^https?:\/\/(www\.)?/i, ''), size: 18, color: link ? accent : soft });
		contact.push(link ? new ExternalHyperlink({ link, children: [run] }) : run);
	});
	if (contact.length) children.push(new Paragraph({ alignment: AlignmentType.CENTER, spacing: { after: 240 }, border: { bottom: { style: BorderStyle.SINGLE, size: 6, space: 8, color: 'DDDDDD' } }, children: contact }));

	const title = (text) => new Paragraph({ spacing: { before: 220, after: 100 }, keepNext: true, border: { bottom: { style: BorderStyle.SINGLE, size: 4, space: 4, color: 'E7E7E3' } }, children: [new TextRun({ text: text.toUpperCase(), bold: true, size: 20, color: ink })] });
	const head = (text, dates) => new Paragraph({ spacing: { before: 80, after: 10 }, keepNext: true, tabStops: [{ type: TabStopType.RIGHT, position: rightTab }], children: [new TextRun({ text, bold: true, size: 22, color: ink }), ...(dates ? [new TextRun({ text: `\t${dates}`, size: 18, color: '888888' })] : [])] });
	const line = (text, options = {}) => new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text, size: 19, color: soft, ...options })] });
	const bullet = (text) => new Paragraph({ bullet: { level: 0 }, spacing: { after: 40 }, children: [new TextRun({ text, size: 20, color: '3A3E40' })] });

	if (cv.summary?.trim()) {
		children.push(title(labels.summary));
		children.push(new Paragraph({ spacing: { after: 80 }, children: [new TextRun({ text: cv.summary, size: 20, color: '33373A' })] }));
	}
	if (cv.experience?.length) {
		children.push(title(labels.experience));
		cv.experience.forEach((job) => {
			children.push(head(job.title, dateRange(job, labels, locale)));
			const org = [job.company, job.location].filter(Boolean).join(' · ');
			if (org) children.push(line(org));
			(job.highlights || []).filter((text) => text.trim()).forEach((text) => children.push(bullet(text)));
		});
	}
	if (cv.education?.length) {
		children.push(title(labels.education));
		cv.education.forEach((school) => {
			const degree = [school.degree, school.field].filter(Boolean).join(', ');
			children.push(head(degree || school.school, dateRange(school, labels, locale)));
			if (degree) children.push(line(school.school));
		});
	}
	if (cv.skills?.length) {
		children.push(title(labels.skills));
		children.push(new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: cv.skills.join(', '), size: 20, color: '3A3E40' })] }));
	}
	if (cv.projects?.length) {
		children.push(title(labels.projects));
		cv.projects.forEach((project) => {
			children.push(new Paragraph({ spacing: { before: 80, after: 10 }, keepNext: true, children: [
				new TextRun({ text: project.name, bold: true, size: 22, color: ink }),
				...(project.url ? [new TextRun({ text: '  ·  ', size: 19, color: 'AAAAAA' }), new ExternalHyperlink({ link: toUrl(project.url.trim()), children: [new TextRun({ text: project.url.replace(/^https?:\/\/(www\.)?/i, ''), size: 19, color: accent })] })] : []),
			] }));
			if (project.summary) children.push(line(project.summary, { italics: true }));
		});
	}
	if (cv.languages?.length) {
		children.push(title(labels.languages));
		children.push(new Paragraph({ spacing: { after: 40 }, children: [new TextRun({ text: cv.languages.join(', '), size: 20, color: '3A3E40' })] }));
	}

	const doc = new Document({
		title: cv.name || fileName,
		creator: cv.name || undefined,
		styles: { default: { document: { run: { font: 'Calibri' } } } },
		sections: [{ properties: { page: { size: page, margin: { top: margin, bottom: margin, left: margin, right: margin } } }, children }],
	});
	const blob = await Packer.toBlob(doc);
	const link = document.createElement('a');
	link.href = URL.createObjectURL(blob);
	link.download = `${fileName}.docx`;
	document.body.appendChild(link);
	link.click();
	link.remove();
	setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}
