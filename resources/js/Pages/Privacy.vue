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
	{ id: 'who', title: text('Who we are', 'Qui sommes-nous'), body: [
		text('Nextward is run by [[legal name of the company or person running Nextward]], [[registered address]], [[company registration number (RNE), if any]]. We decide why and how your data is used, so we are the data controller.', 'Nextward est géré par [[nom légal de la société ou de la personne qui gère Nextward]], [[adresse du siège]], [[numéro d’immatriculation (RNE), le cas échéant]]. Nous décidons pourquoi et comment vos données sont utilisées : nous sommes donc le responsable du traitement.'),
		text('For any question about your data, write to alabaganne9@gmail.com.', 'Pour toute question sur vos données, écrivez à alabaganne9@gmail.com.'),
	] },
	{ id: 'data', title: text('What we keep', 'Les données que nous gardons'), body: [
		text('We only keep what we need to run Nextward:', 'Nous ne gardons que ce qui est utile au fonctionnement de Nextward :'),
		[
			text('Account: your name, email address and password. The password is stored in a scrambled form that we cannot read. Phone number and LinkedIn link are optional.', 'Compte : votre nom, votre adresse e-mail et votre mot de passe. Le mot de passe est enregistré sous une forme chiffrée que nous ne pouvons pas lire. Le numéro de téléphone et le lien LinkedIn sont facultatifs.'),
			text('Candidate profile: career stage, field, city or country, short bio, skills and the kind of roles you want.', 'Profil candidat : niveau d’études ou d’expérience, domaine, ville ou pays, courte présentation, compétences et types de postes recherchés.'),
			text('Company profile: company name, logo, website, description and location. This profile is public.', 'Profil entreprise : nom, logo, site web, description et lieu. Ce profil est public.'),
			text('Applications sent through Nextward: your message, cover letter, CV and other files you attach, and the status of the application.', 'Candidatures envoyées via Nextward : votre message, votre lettre de motivation, votre CV et les autres fichiers joints, ainsi que l’état de la candidature.'),
			text('Saved roles and tracking: the opportunities you save, the ones you mark as applied elsewhere, and your private notes.', 'Offres enregistrées et suivi : les offres que vous enregistrez, celles où vous indiquez avoir postulé ailleurs et vos notes privées.'),
			text('Viewed roles: the opportunities you open. Before you log in, this list stays in your browser only. When you log in, we copy it to your account so you see it on all your devices.', 'Offres consultées : les offres que vous ouvrez. Avant la connexion, cette liste reste uniquement dans votre navigateur. Quand vous vous connectez, nous la copions dans votre compte pour la retrouver sur tous vos appareils.'),
			text('Saved searches and email alerts you choose to turn on.', 'Les recherches enregistrées et les alertes e-mail que vous activez.'),
			text('Messages you send to other users and the notifications we show you.', 'Les messages que vous envoyez aux autres utilisateurs et les notifications que nous vous affichons.'),
			text('Technical data: like most websites, our server records your IP address, the page asked for and your browser type, to keep the service working and safe.', 'Données techniques : comme la plupart des sites, notre serveur enregistre votre adresse IP, la page demandée et le type de navigateur, pour que le service fonctionne et reste sûr.'),
		],
	] },
	{ id: 'why', title: text('Why we use it', 'Pourquoi nous les utilisons'), body: [
		[
			text('To create and run your account and show you opportunities that fit you. This is needed to give you the service you signed up for.', 'Pour créer et gérer votre compte et vous montrer des offres qui vous correspondent. C’est nécessaire pour vous fournir le service demandé.'),
			text('To send your application to the company you chose, and let you both follow it.', 'Pour transmettre votre candidature à l’entreprise choisie et vous permettre à tous les deux d’en suivre l’avancement.'),
			text('To send account emails (email check, password reset) and the alerts you turned on. You can stop alerts at any time with the link in each email.', 'Pour envoyer les e-mails liés au compte (vérification, mot de passe oublié) et les alertes que vous avez activées. Vous pouvez arrêter les alertes à tout moment avec le lien présent dans chaque e-mail.'),
			text('To keep Nextward safe, stop abuse and fix problems. This is our legitimate interest.', 'Pour protéger Nextward, empêcher les abus et corriger les problèmes. C’est notre intérêt légitime.'),
		],
		text('We do not sell your data, we do not show ads, and we do not use tracking or analytics cookies.', 'Nous ne vendons pas vos données, nous n’affichons pas de publicité et nous n’utilisons pas de cookies de suivi ou de mesure d’audience.'),
	] },
	{ id: 'sharing', title: text('Who sees it', 'Qui y a accès'), body: [
		[
			text('Companies: when you apply through Nextward, the company that receives your application sees it, with your CV, your files and the profile details shown with it. Companies cannot browse candidate profiles.', 'Les entreprises : quand vous postulez via Nextward, l’entreprise qui reçoit votre candidature la voit, avec votre CV, vos fichiers et les informations de profil qui l’accompagnent. Les entreprises ne peuvent pas parcourir les profils des candidats.'),
			text('The people you write to, for messages.', 'Les personnes à qui vous écrivez, pour les messages.'),
			text('Everyone, for company profiles and the opportunities companies publish.', 'Tout le monde, pour les profils d’entreprise et les offres publiées.'),
			text('The Nextward team, only when needed to run the service or answer you.', 'L’équipe Nextward, uniquement quand c’est nécessaire pour faire fonctionner le service ou vous répondre.'),
			text('Our service providers, who work for us under contract: hosting by [[hosting provider and server country]] and email sending by [[email provider]].', 'Nos prestataires, qui travaillent pour nous sous contrat : hébergement par [[hébergeur et pays des serveurs]] et envoi des e-mails par [[prestataire e-mail]].'),
			text('Public authorities, only when the law requires it.', 'Les autorités publiques, uniquement quand la loi l’exige.'),
		],
	] },
	{ id: 'sources', title: text('Listings from other sites', 'Offres venant d’autres sites'), body: [
		text('Many listings are imported from public job sites and company career pages. They link to the original page. Opening that link does not send your CV or any data to the other site. If you apply there, that site’s own privacy policy applies.', 'Beaucoup d’offres sont importées depuis des sites d’emploi publics et des pages carrières d’entreprises. Elles renvoient vers l’annonce d’origine. Ouvrir ce lien n’envoie ni votre CV ni aucune donnée à l’autre site. Si vous y postulez, c’est la politique de confidentialité de ce site qui s’applique.'),
	] },
	{ id: 'retention', title: text('How long we keep it', 'Combien de temps nous les gardons'), body: [
		[
			text('Account, profile, saved roles, viewed roles, searches and messages: as long as your account is open. They are deleted when you delete your account.', 'Compte, profil, offres enregistrées et consultées, recherches et messages : tant que votre compte est ouvert. Ils sont supprimés quand vous supprimez votre compte.'),
			text('Applications and attached files: as long as your account is open, unless you withdraw the application sooner. A company that downloaded your CV keeps that copy under its own rules.', 'Candidatures et fichiers joints : tant que votre compte est ouvert, sauf si vous retirez la candidature avant. Une entreprise qui a téléchargé votre CV garde cette copie selon ses propres règles.'),
			text('Viewed roles kept in your browser: until you clear your browser data.', 'Offres consultées gardées dans votre navigateur : jusqu’à ce que vous effaciez les données de votre navigateur.'),
			text('Server logs: [[how long server logs are kept]]. Backups: [[how long backups are kept]].', 'Journaux du serveur : [[durée de conservation des journaux]]. Sauvegardes : [[durée de conservation des sauvegardes]].'),
		],
	] },
	{ id: 'delete', title: text('Delete your account', 'Supprimer votre compte'), body: [
		text('You can delete your account at any time: log in, open Settings, choose Delete account and confirm with your password. You can also ask us by email and we will do it for you.', 'Vous pouvez supprimer votre compte à tout moment : connectez-vous, ouvrez Paramètres, choisissez Supprimer le compte et confirmez avec votre mot de passe. Vous pouvez aussi nous le demander par e-mail et nous le ferons pour vous.'),
		text('For candidates, this deletes your profile, applications, attached files, saved and viewed roles, searches, messages and notifications. For companies, your listings are closed, and your company name stays on the applications candidates already sent you so they know who they applied to.', 'Pour un candidat, cela supprime votre profil, vos candidatures, les fichiers joints, les offres enregistrées et consultées, les recherches, les messages et les notifications. Pour une entreprise, vos offres sont fermées et le nom de l’entreprise reste sur les candidatures déjà reçues, pour que les candidats sachent à qui ils ont postulé.'),
	] },
	{ id: 'rights', title: text('Your rights', 'Vos droits'), body: [
		text('You can ask to see the data we hold about you, correct it, delete it, or object to how we use it. You can also withdraw a consent you gave, such as email alerts. Write to alabaganne9@gmail.com and we will answer within one month.', 'Vous pouvez demander à voir les données que nous avons sur vous, les corriger, les supprimer ou vous opposer à leur utilisation. Vous pouvez aussi retirer un consentement donné, par exemple pour les alertes e-mail. Écrivez à alabaganne9@gmail.com : nous répondrons sous un mois.'),
		text('Tunisia: we follow the Organic Law n° 2004-63 of 27 July 2004 on the protection of personal data. You can complain to the national authority, the INPDP (Instance Nationale de Protection des Données Personnelles). Our declaration to the INPDP: [[INPDP declaration or authorization number]].', 'Tunisie : nous respectons la loi organique n° 2004-63 du 27 juillet 2004 portant sur la protection des données à caractère personnel. Vous pouvez saisir l’autorité nationale, l’INPDP (Instance Nationale de Protection des Données Personnelles). Notre déclaration auprès de l’INPDP : [[numéro de déclaration ou d’autorisation INPDP]].'),
		text('European Union: if you live in the EU, the GDPR also gives you the right to get your data in a reusable format and to complain to the data protection authority of your country.', 'Union européenne : si vous vivez dans l’UE, le RGPD vous donne aussi le droit de recevoir vos données dans un format réutilisable et de saisir l’autorité de protection des données de votre pays.'),
		text('Your data is stored on servers in [[server country]]. When data leaves Tunisia or the EU, we only send it where the law allows and with the safeguards it requires.', 'Vos données sont stockées sur des serveurs situés en [[pays des serveurs]]. Quand des données quittent la Tunisie ou l’UE, nous ne les envoyons que là où la loi le permet et avec les garanties qu’elle exige.'),
	] },
	{ id: 'cookies', title: text('Cookies', 'Cookies'), body: [
		text('We only use the cookies Nextward needs to work, so we do not ask for cookie consent:', 'Nous n’utilisons que les cookies nécessaires au fonctionnement de Nextward, c’est pourquoi nous ne demandons pas de consentement :'),
		[
			text('A session cookie that keeps you logged in while you browse. It ends after two hours without activity or when you log out.', 'Un cookie de session qui vous garde connecté pendant la navigation. Il expire après deux heures d’inactivité ou à la déconnexion.'),
			text('A security cookie (XSRF-TOKEN) that protects forms against fake requests.', 'Un cookie de sécurité (XSRF-TOKEN) qui protège les formulaires contre les fausses requêtes.'),
			text('A “Remember me” cookie, only if you tick that box when you log in. It keeps you logged in for up to 400 days or until you log out.', 'Un cookie « Se souvenir de moi », seulement si vous cochez cette case à la connexion. Il vous garde connecté jusqu’à 400 jours ou jusqu’à la déconnexion.'),
		],
		text('Your browser’s local storage also keeps the roles you viewed or saved before logging in. Fonts and company logos are stored on our own servers, so loading them does not contact any other site.', 'Le stockage local de votre navigateur garde aussi les offres consultées ou enregistrées avant la connexion. Les polices et les logos d’entreprise sont hébergés sur nos propres serveurs : leur chargement ne contacte aucun autre site.'),
	] },
	{ id: 'security', title: text('Security', 'Sécurité'), body: [
		text('Pages are served over HTTPS, passwords are scrambled, and files attached to an application can only be opened by you and the company you sent them to. No system is perfect: if a breach puts your data at risk, we will tell you and the authorities as the law requires.', 'Les pages sont servies en HTTPS, les mots de passe sont chiffrés et les fichiers joints à une candidature ne peuvent être ouverts que par vous et l’entreprise destinataire. Aucun système n’est parfait : si une fuite met vos données en danger, nous vous préviendrons, ainsi que les autorités, comme la loi l’exige.'),
	] },
	{ id: 'changes', title: text('Changes to this policy', 'Modifications'), body: [
		text('If we change this policy, we will update the date at the top. For important changes, we will also tell account holders by email.', 'Si nous modifions cette politique, nous mettrons à jour la date en haut de la page. Pour les changements importants, nous préviendrons aussi les titulaires de compte par e-mail.'),
	] },
]);
</script>

<template>
	<PublicLayout>
		<LegalPage
			:label="text('Legal', 'Mentions légales')"
			:title="text('Privacy policy', 'Politique de confidentialité')"
			:lead="text('What data Nextward keeps, why, who sees it, and how you stay in control of it.', 'Les données que Nextward garde, pourquoi, qui y a accès et comment vous en gardez le contrôle.')"
			:updated="text('Last updated: 16 October 2026', 'Dernière mise à jour : 16 octobre 2026')"
			:toc-label="text('On this page', 'Sur cette page')"
			:sections="sections"
		>
			<p class="mt-12 text-sm text-slate-500">{{ text('See also our', 'Voir aussi nos') }} <Link :href="`${prefix}/terms`" class="text-blue-700 underline underline-offset-4">{{ text('terms of use', 'conditions d’utilisation') }}</Link>.</p>
		</LegalPage>
	</PublicLayout>
</template>
