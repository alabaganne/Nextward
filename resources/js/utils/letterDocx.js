// Builds a Word file of a cover letter in the browser, laid out like the PDF.
export async function downloadLetterDocx({ letter, name = '', contact = [], locale = 'en', fileName = 'cover-letter' }) {
	const { Document, Packer, Paragraph, TextRun } = await import('docx');
	const font = 'Calibri';
	const date = new Intl.DateTimeFormat(locale === 'en' ? 'en-GB' : locale, { dateStyle: 'long' }).format(new Date());
	const blocks = letter.trim().split(/\n\s*\n/).map((block) => new Paragraph({
		spacing: { after: 200, line: 300 },
		children: block.split('\n').map((line, index) => new TextRun({ text: line, font, size: 22, ...(index ? { break: 1 } : {}) })),
	}));
	const doc = new Document({
		creator: name,
		title: name,
		lastModifiedBy: name,
		sections: [{
			properties: { page: { margin: { top: 1134, bottom: 1134, left: 1276, right: 1276 } } },
			children: [
				...(name ? [new Paragraph({ children: [new TextRun({ text: name, font, size: 30, bold: true, color: '0D2353' })] })] : []),
				...(contact.length ? [new Paragraph({ children: [new TextRun({ text: contact.join('  ·  '), font, size: 19, color: '475569' })] })] : []),
				new Paragraph({ spacing: { before: 480, after: 360 }, children: [new TextRun({ text: date, font, size: 22, color: '475569' })] }),
				...blocks,
			],
		}],
	});
	const blob = await Packer.toBlob(doc);
	const link = document.createElement('a');
	link.href = URL.createObjectURL(blob);
	link.download = `${fileName}.docx`;
	link.click();
	setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}

export const letterFileName = (name, company, locale) => [name || 'nextward', locale === 'fr' ? 'lettre' : 'cover letter', company]
	.filter(Boolean).join(' ').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
