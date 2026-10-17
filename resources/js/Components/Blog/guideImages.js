// Article photos by slug. French slugs differ from English ones, so both are listed.
const images = [
	{ slugs: ['check-remote-job-eligibility-from-tunisia', 'verifier-eligibilite-offre-remote-tunisie'], file: 'guide-remote-work', width: 560, height: 298, alt: ['A laptop, a globe and a world map on a desk by a window', 'Un ordinateur portable, un globe et une carte du monde sur un bureau près d’une fenêtre'] },
	{ slugs: ['best-internship-application-tips', 'conseils-candidature-stage'], file: 'guide-cv-application', width: 406, height: 164, alt: ['A printed CV next to a pen', 'Un CV imprimé à côté d’un stylo'] },
	{ slugs: ['compare-job-opportunities-as-a-professional', 'comparer-des-offres-emploi'], file: 'guide-job-shortlist', width: 407, height: 164, alt: ['A notebook with a handwritten table of opportunities', 'Un carnet avec un tableau d’opportunités écrit à la main'] },
	{ slugs: ['how-to-find-an-internship-as-a-student', 'trouver-un-stage-etudiant'], file: 'guide-find-internship', width: 406, height: 180, alt: ['Four students working together around a laptop in a library', 'Quatre étudiants travaillant ensemble autour d’un ordinateur dans une bibliothèque'] },
	{ slugs: ['how-companies-can-hire-interns-faster', 'recruter-des-stagiaires-plus-vite'], file: 'guide-hiring-interns', width: 407, height: 180, alt: ['Three people reviewing documents at a meeting table', 'Trois personnes examinant des documents autour d’une table de réunion'] },
	{ slugs: ['what-makes-a-good-internship-platform', 'bonne-plateforme-de-stage'], file: 'guide-platform', width: 380, height: 155, alt: ['A young woman thinking in front of a laptop', 'Une jeune femme pensive devant un ordinateur portable'] },
];

export function guideImage(post) {
	if (post?.cover) return { src: post.cover, alt: post.cover_alt || '', width: 1200, height: 630 };
	const image = images.find((item) => item.slugs.includes(post?.slug));
	if (!image) return null;
	return { src: `/nextward-assets/landing-light-v1/${image.file}.webp`, alt: image.alt[post.locale === 'fr' ? 1 : 0], width: image.width, height: image.height };
}
