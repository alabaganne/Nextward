<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public.vue';
import LegalPage from '@/Shared/LegalPage.vue';

const page = usePage();
const fr = computed(() => page.props.locale === 'fr');
const prefix = computed(() => fr.value ? '/fr' : '');
const text = (en, french) => fr.value ? french : en;
const sections = computed(() => [
	{ id: 'about', title: text('About these terms', 'À propos de ces conditions'), body: [
		text('Nextward is a website for finding and posting internships and jobs, run by [[legal name of the company or person running Nextward]], [[registered address]]. These terms apply to everyone who uses Nextward. By creating an account, you accept them and our privacy policy.', 'Nextward est un site pour trouver et publier des stages et des emplois, géré par [[nom légal de la société ou de la personne qui gère Nextward]], [[adresse du siège]]. Ces conditions s’appliquent à toute personne qui utilise Nextward. En créant un compte, vous les acceptez, ainsi que notre politique de confidentialité.'),
	] },
	{ id: 'accounts', title: text('Your account', 'Votre compte'), body: [
		[
			text('You must be at least [[minimum age]] years old to create an account.', 'Vous devez avoir au moins [[âge minimum]] ans pour créer un compte.'),
			text('Give true information and keep it up to date. One account per person or company.', 'Donnez des informations exactes et tenez-les à jour. Un seul compte par personne ou par entreprise.'),
			text('Keep your password private. You are responsible for what happens in your account.', 'Gardez votre mot de passe secret. Vous êtes responsable de ce qui se passe sur votre compte.'),
		],
	] },
	{ id: 'candidates', title: text('For candidates', 'Pour les candidats'), body: [
		[
			text('Only send your own CV and files, and make sure what you write is true.', 'N’envoyez que votre propre CV et vos propres fichiers, et vérifiez que ce que vous écrivez est exact.'),
			text('Nextward helps you find and apply to opportunities. We are not the employer and we cannot promise you an interview or a job.', 'Nextward vous aide à trouver des offres et à y postuler. Nous ne sommes pas l’employeur et ne pouvons pas vous promettre un entretien ou un poste.'),
			text('Applying through Nextward is free. Never pay anyone to get an internship or a job listed here, and tell us if a company asks you to.', 'Postuler via Nextward est gratuit. Ne payez jamais pour obtenir un stage ou un emploi publié ici, et prévenez-nous si une entreprise vous le demande.'),
		],
	] },
	{ id: 'companies', title: text('For companies', 'Pour les entreprises'), body: [
		[
			text('Only post real roles that you are allowed to fill, with honest details about the work, place, pay and conditions.', 'Ne publiez que de vraies offres que vous êtes en droit de pourvoir, avec des informations honnêtes sur le poste, le lieu, la rémunération et les conditions.'),
			text('Follow labour law and do not discriminate. Never ask candidates for money.', 'Respectez le droit du travail et ne faites pas de discrimination. Ne demandez jamais d’argent aux candidats.'),
			text('Use the applications you receive only to recruit for your roles. You are responsible for handling candidate data under the data protection law that applies to you, and for deleting it when you no longer need it.', 'N’utilisez les candidatures reçues que pour recruter sur vos offres. Vous êtes responsable du traitement des données des candidats selon la loi sur la protection des données qui vous concerne, et de leur suppression quand vous n’en avez plus besoin.'),
		],
	] },
	{ id: 'listings', title: text('Listings from other sites', 'Offres venant d’autres sites'), body: [
		text('Some listings are imported from public job sites and company career pages. Each one names its source and links to the original page, where you apply. We check sources regularly, but details can change or be wrong: always confirm them on the original page. An imported listing does not mean the employer has an account on Nextward or works with us.', 'Certaines offres sont importées depuis des sites d’emploi publics et des pages carrières d’entreprises. Chacune indique sa source et renvoie vers l’annonce d’origine, où vous postulez. Nous vérifions les sources régulièrement, mais les informations peuvent changer ou être inexactes : vérifiez-les toujours sur l’annonce d’origine. Une offre importée ne signifie pas que l’employeur a un compte sur Nextward ou travaille avec nous.'),
		text('If you own a source or a listing and want it changed or removed, contact us and we will act quickly.', 'Si vous êtes propriétaire d’une source ou d’une offre et souhaitez la modifier ou la retirer, contactez-nous : nous agirons rapidement.'),
	] },
	{ id: 'rules', title: text('What you must not do', 'Ce qui est interdit'), body: [
		[
			text('Post fake, misleading, illegal or offensive content.', 'Publier un contenu faux, trompeur, illégal ou offensant.'),
			text('Spam, harass or scam other users.', 'Envoyer du spam, harceler ou arnaquer d’autres utilisateurs.'),
			text('Collect other users’ data, or copy the site with bots or scrapers.', 'Collecter les données d’autres utilisateurs, ou copier le site avec des robots ou des outils d’extraction.'),
			text('Try to break into accounts or systems, or upload harmful files.', 'Tenter d’accéder à des comptes ou à des systèmes sans autorisation, ou envoyer des fichiers malveillants.'),
		],
		text('We can remove content or suspend an account that breaks these rules.', 'Nous pouvons retirer un contenu ou suspendre un compte qui ne respecte pas ces règles.'),
	] },
	{ id: 'content', title: text('Your content', 'Votre contenu'), body: [
		text('You keep the rights to what you post (profile, CV, listings, messages). You allow us to store and show it on Nextward only as needed to run the service, for example to show your listing to candidates or send your application to a company. The Nextward name, logo and design belong to us.', 'Vous gardez les droits sur ce que vous publiez (profil, CV, offres, messages). Vous nous autorisez à le stocker et à l’afficher sur Nextward uniquement pour faire fonctionner le service, par exemple pour montrer votre offre aux candidats ou envoyer votre candidature à une entreprise. Le nom, le logo et le design de Nextward nous appartiennent.'),
	] },
	{ id: 'liability', title: text('Our responsibility', 'Notre responsabilité'), body: [
		text('We work to keep Nextward available and correct, but we provide it as it is, without a promise that it will always work or that every listing is accurate. We are not part of the hiring decision or of any work contract between a candidate and a company. As far as the law allows, we are not responsible for losses that come from using the site or from a listing. Nothing in these terms limits rights the law gives you that cannot be removed.', 'Nous faisons en sorte que Nextward reste disponible et exact, mais nous le fournissons tel quel, sans garantir qu’il fonctionnera toujours ni que chaque offre est exacte. Nous ne participons ni à la décision d’embauche ni au contrat de travail entre un candidat et une entreprise. Dans les limites permises par la loi, nous ne sommes pas responsables des pertes liées à l’utilisation du site ou d’une offre. Rien dans ces conditions ne limite les droits que la loi vous accorde et auxquels on ne peut pas renoncer.'),
	] },
	{ id: 'ending', title: text('Closing your account', 'Fermer votre compte'), body: [
		text('You can delete your account at any time in Settings. We can close an account that breaks these terms, after telling you why when we can.', 'Vous pouvez supprimer votre compte à tout moment dans les Paramètres. Nous pouvons fermer un compte qui ne respecte pas ces conditions, en vous en expliquant la raison quand c’est possible.'),
	] },
	{ id: 'law', title: text('Changes and law', 'Modifications et droit applicable'), body: [
		text('If we change these terms, we will update the date at the top and tell account holders by email about important changes. If you keep using Nextward after that, the new terms apply.', 'Si nous modifions ces conditions, nous mettrons à jour la date en haut de la page et préviendrons les titulaires de compte par e-mail en cas de changement important. Si vous continuez à utiliser Nextward ensuite, les nouvelles conditions s’appliquent.'),
		text('These terms follow Tunisian law. Any dispute goes to the courts of [[city of the competent courts]], unless the law of your country gives you the right to go to your local courts.', 'Ces conditions sont soumises au droit tunisien. Tout litige relève des tribunaux de [[ville des tribunaux compétents]], sauf si la loi de votre pays vous permet de saisir vos tribunaux locaux.'),
		text('Questions? Write to alabaganne9@gmail.com.', 'Des questions ? Écrivez à alabaganne9@gmail.com.'),
	] },
]);
</script>

<template>
	<PublicLayout>
		<LegalPage
			:label="text('Legal', 'Mentions légales')"
			:title="text('Terms of use', 'Conditions d’utilisation')"
			:lead="text('The rules for using Nextward, for candidates and for companies.', 'Les règles d’utilisation de Nextward, pour les candidats et pour les entreprises.')"
			:updated="text('Last updated: 16 October 2026', 'Dernière mise à jour : 16 octobre 2026')"
			:toc-label="text('On this page', 'Sur cette page')"
			:sections="sections"
		>
			<p class="mt-12 text-sm text-slate-500">{{ text('See also our', 'Voir aussi notre') }} <Link :href="`${prefix}/privacy`" class="text-blue-700 underline underline-offset-4">{{ text('privacy policy', 'politique de confidentialité') }}</Link>.</p>
		</LegalPage>
	</PublicLayout>
</template>
