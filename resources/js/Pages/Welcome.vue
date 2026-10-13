<template>
	<public-layout>
		<div class="landing">
			<section aria-labelledby="hero-title" class="hero public-container">
				<div class="hero-copy">
					<p class="landing-label"><span class="label-dot" aria-hidden="true"></span> Find your internship</p>
					<h1 id="hero-title">Small step.<br /><span>Big beginning.</span></h1>
					<p class="hero-description">Your next chapter starts with the right internship. Explore opportunities, find your fit, and connect with the companies you could grow with.</p>
					<div class="hero-actions">
						<Link :href="route('internships.index')" class="cta cta-primary focus-ring">Explore internships <icon name="arrow-up-right" class="action-icon" /></Link>
						<a href="#how-it-works" class="landing-link focus-ring"><span class="link-label">See how it works</span> <icon name="arrow-down" class="action-icon" /></a>
					</div>
					<p class="hero-note"><icon name="check-circle" aria-hidden="true" /> No account needed to explore.</p>
				</div>
				<figure class="hero-art">
					<img src="/internly-assets/landing/career-steps.webp" alt="Editorial illustration of a student carrying a portfolio up a set of blue steps toward a star" width="1254" height="1254" fetchpriority="high" decoding="async" />
					<figcaption>A little ambition. A world of possibility.</figcaption>
				</figure>
			</section>

			<section id="how-it-works" aria-labelledby="how-title" class="how-section public-container">
				<div class="how-heading">
					<p class="landing-label">How it works</p>
					<h2 id="how-title">From looking to <span class="text-brand">getting started.</span></h2>
				</div>
				<ol class="steps">
					<li v-for="(step, index) in steps" :key="step.title">
						<span class="step-number" aria-hidden="true">0{{ index + 1 }}</span>
						<div><h3>{{ step.title }}</h3><p>{{ step.text }}</p></div>
					</li>
				</ol>
			</section>

			<section aria-labelledby="students-title" class="student-section">
				<div class="public-container student-grid">
					<div>
						<p class="landing-label">For students</p>
						<h2 id="students-title" class="section-title">Less searching.<br />More moving forward.</h2>
						<p class="section-description">A place to find your first opportunity, and a little clarity on what comes next.</p>
						<ul class="feature-list">
							<li v-for="feature in studentFeatures" :key="feature.title">
								<icon name="check-circle" aria-hidden="true" />
								<div><h3>{{ feature.title }}</h3><p>{{ feature.text }}</p></div>
							</li>
						</ul>
						<Link :href="currentUser ? route('internships.index') : route('register', { account: 'student' })" class="landing-link focus-ring"><span class="link-label">{{ currentUser ? 'Find your next internship' : 'Create your student account' }}</span> <icon name="arrow-up-right" class="action-icon" /></Link>
					</div>
					<figure class="application-preview">
						<figcaption class="preview-caption">A little clarity, at every step <span>Illustrative preview</span></figcaption>
						<div class="preview-heading"><icon name="document-text" aria-hidden="true" /><h3>Your applications</h3></div>
						<ul class="application-list">
							<li v-for="application in exampleApplications" :key="application.title">
								<div><p class="application-title">{{ application.title }}</p><p class="application-detail">{{ application.detail }}</p></div>
								<span class="application-status" :class="application.offer ? 'status-offer' : 'status-review'">{{ application.status }}</span>
							</li>
						</ul>
						<p class="preview-note"><icon name="check-circle" aria-hidden="true" /> Every application, one clear view.</p>
					</figure>
				</div>
			</section>

			<section aria-labelledby="companies-title" class="company-section">
				<img class="company-background" src="/internly-assets/landing/workspace.webp" alt="" width="1672" height="941" loading="lazy" decoding="async" />
				<div class="company-overlay" aria-hidden="true"></div>
				<div class="public-container company-content">
					<div class="company-copy">
						<p class="landing-label">For companies</p>
						<h2 id="companies-title" class="section-title">Fresh perspectives.<br />Your next great hire.</h2>
						<p class="section-description">Make room for emerging talent. Publish your internship and keep applications and conversations in one place.</p>
						<ul class="feature-list">
							<li v-for="feature in companyFeatures" :key="feature.title">
								<icon name="check-circle" aria-hidden="true" />
								<div><h3>{{ feature.title }}</h3><p>{{ feature.text }}</p></div>
							</li>
						</ul>
						<Link v-if="companyAction" :href="companyAction" class="cta cta-light focus-ring">{{ isCompany ? 'Post an internship' : 'Start hiring on Internly' }} <icon name="arrow-up-right" class="action-icon" /></Link>
						<Link v-else :href="route('blog.show', 'how-companies-can-hire-interns-faster')" class="cta cta-light focus-ring">Read the hiring guide <icon name="arrow-up-right" class="action-icon" /></Link>
					</div>
					<p class="company-aside" aria-hidden="true">Great work starts<br />with a first opportunity.</p>
				</div>
			</section>

			<section v-if="posts.length" aria-labelledby="guides-title" class="guides-section public-container">
				<div class="section-heading">
					<div><p class="landing-label">The reading room</p><h2 id="guides-title" class="section-title">A little guidance goes a long way.</h2></div>
					<Link :href="route('blog.index')" class="landing-link focus-ring"><span class="link-label">All guides</span> <icon name="arrow-up-right" class="action-icon" /></Link>
				</div>
				<div class="guide-list">
					<Link v-for="post in posts" :key="post.slug" :href="route('blog.show', post.slug)" class="guide-row focus-ring">
						<span class="guide-category">{{ post.category }}</span>
						<div><h3>{{ post.title }}</h3><p>{{ post.excerpt }}</p></div>
						<span class="guide-time">{{ post.read_time }}</span><icon name="arrow-up-right" class="guide-arrow" />
					</Link>
				</div>
			</section>

			<section aria-labelledby="faq-title" class="faq-section public-container">
				<div>
					<p class="landing-label">Good to know</p>
					<h2 id="faq-title" class="section-title">A few questions.<br />Clear answers.</h2>
					<p class="section-description">Something else on your mind?</p>
					<Link :href="route('contact')" class="landing-link focus-ring"><span class="link-label">Let’s talk</span> <icon name="arrow-up-right" class="action-icon" /></Link>
				</div>
				<div class="faq-list">
					<details v-for="item in faqs" :key="item.question" class="faq-item">
						<summary class="focus-ring">{{ item.question }}<icon name="plus" class="faq-toggle" /></summary>
						<p>{{ item.answer }}</p>
					</details>
				</div>
			</section>

			<section aria-labelledby="cta-title" class="closing-section">
				<div class="public-container closing-content">
					<div><p class="landing-label">Here’s to what comes next</p><h2 id="cta-title" class="section-title">Your beginning is out there.</h2><p>Let’s find it.</p></div>
					<div class="closing-actions">
						<Link :href="route('internships.index')" class="cta cta-light focus-ring">Explore internships <icon name="arrow-up-right" class="action-icon" /></Link>
						<Link v-if="companyAction" :href="companyAction" class="landing-link focus-ring"><span class="link-label">Hiring? Post an internship</span> <icon name="arrow-up-right" class="action-icon" /></Link>
					</div>
				</div>
			</section>
		</div>
	</public-layout>
</template>

<script>
import EmptyLayout from '@/Layouts/Empty.vue';
import PublicLayout from '@/Layouts/Public.vue';

export default {
	layout: EmptyLayout,
	components: { PublicLayout },
	props: {
		posts: { type: Array, default: () => [] },
	},
	data() {
		return {
			steps: [
				{ title: 'Find your fit', text: 'Explore open internships by field, city, or company. Take a look around before signing up.' },
				{ title: 'Make your introduction', text: 'Create a student account. Send your cover letter and the documents that tell your story.' },
				{ title: 'Keep things moving', text: 'Follow your application’s status and message companies directly, all in one place.' },
			],
			studentFeatures: [
				{ title: 'A search that makes sense', text: 'Filter by field, city, and company. Save the internships you want to come back to.' },
				{ title: 'Know where you stand', text: 'Follow your applications from the first review to an interview or offer.' },
				{ title: 'Start a real conversation', text: 'Message a company from its profile and keep the conversation here.' },
			],
			companyFeatures: [
				{ title: 'Introduce your company', text: 'Give students a feel for who you are with a company profile.' },
				{ title: 'Publish, then review', text: 'Post a role and review cover letters and attachments in one place.' },
				{ title: 'Close the loop', text: 'Update applications as you review them. Students are notified when you reply.' },
			],
			// Examples explain the existing status workflow; they are not live listings or applications.
			exampleApplications: [
				{ title: 'Design internship', detail: 'Cover letter & portfolio', status: 'In review', offer: false },
				{ title: 'Engineering internship', detail: 'Cover letter & CV', status: 'Offer', offer: true },
				{ title: 'Marketing internship', detail: 'Cover letter & CV', status: 'In review', offer: false },
			],
			faqs: [
				{ question: 'Do I need an account to explore?', answer: 'No. Internship listings and their details are public. You need a student account to apply, save internships, and follow your applications.' },
				{ question: 'What do I need to apply?', answer: 'A cover letter of at least 200 characters. You can add a note for the company and attach up to 10 files, such as your CV or portfolio, each up to 10 MB.' },
				{ question: 'How do I follow my application?', answer: 'Follow each stage: submitted, viewed, in review, interview, offer, or closed. You get a notification when the company replies, and you can edit your application until it receives an offer or is closed.' },
				{ question: 'How can my company post an internship?', answer: 'Create a company account with your website, logo, city, and a short description. Once your profile is complete, you can publish internships with a description, field, closing date, and optional attachments.' },
				{ question: 'Are companies independently verified?', answer: 'Not independently. Each listing shows the company’s contact details and website, so you can check who is hiring before you apply.' },
				{ question: 'What happens when an internship closes?', answer: 'It leaves the internship list and stops accepting applications. Companies can still review the applications they already received.' },
			],
		};
	},
	computed: {
		isCompany() {
			return this.currentUser?.userable_type === 'company';
		},
		companyAction() {
			if (!this.currentUser) return route('register', { account: 'company' });
			return this.isCompany ? route('internships.create') : null;
		},
	},
};
</script>

<style scoped>
.landing { background: #faf9f6; color: var(--color-navy); }
.landing p { white-space: normal; }
.landing h1, .landing h2, .landing h3 { font-family: var(--font-display); }
.landing-label { display: flex; align-items: center; gap: 10px; font-size: 11px; font-weight: 600; letter-spacing: .15em; text-transform: uppercase; }
.label-dot { height: 7px; width: 7px; border-radius: 50%; background: var(--color-brand); }
.landing-link { display: inline-flex; align-items: center; gap: 15px; padding-block: 5px; font-size: 14px; font-weight: 600; border-radius: 2px; text-decoration: none; transition: color 180ms; }
.landing-link:hover { color: var(--color-brand); }
.link-label { text-decoration: underline; text-decoration-color: #0a173830; text-underline-offset: 6px; transition: text-decoration-color 180ms; }
.landing-link:hover .link-label { text-decoration-color: currentColor; }
.action-icon { width: 18px; height: 18px; flex-shrink: 0; }
.hero { display: grid; grid-template-columns: 1.1fr 1fr; align-items: center; gap: 24px; padding-block: 76px 70px; }
.hero h1 { margin-top: 25px; font-size: clamp(54px, 5.7vw, 80px); font-weight: 600; line-height: 1.05; letter-spacing: -.065em; }
.hero h1 > span { color: var(--color-brand); }
.hero-description { max-width: 470px; margin-top: 25px; font-size: 17px; line-height: 1.8; color: #586171; }
.hero-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 24px; margin-top: 30px; }
.hero .cta { padding: 15px 23px; font-size: 14px; }
.hero-note { display: flex; align-items: center; gap: 7px; margin-top: 22px; font-size: 12px; color: #67707e; }
.hero-note svg { width: 15px; height: 15px; color: var(--color-brand); }
.hero-art { margin: 0; }
.hero-art img { width: 100%; height: auto; object-fit: contain; }
.hero-art figcaption { margin-top: 4px; text-align: center; font-size: 11px; letter-spacing: .035em; color: #67707e; }
.how-section { scroll-margin-top: 100px; padding-block: 35px 50px; border-top: 1px solid #0a17381a; }
.how-heading { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.how-heading h2 { font-size: 19px; font-weight: 500; letter-spacing: -.035em; }
.steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px; margin-top: 40px; }
.steps li { display: flex; align-items: flex-start; gap: 18px; }
.step-number { color: var(--color-brand); font-family: var(--font-display); font-size: 15px; line-height: 1.7; }
.steps h3 { font-size: 18px; font-weight: 600; letter-spacing: -.025em; }
.steps p { margin-top: 9px; font-size: 13px; line-height: 1.8; color: #586171; }
.student-section { background: #fff; padding-block: 100px; border-top: 1px solid #0a173810; }
.student-grid { display: grid; grid-template-columns: 1fr 1fr; align-items: center; gap: 90px; }
.section-title { margin-top: 19px; font-size: clamp(32px, 3.4vw, 46px); font-weight: 500; line-height: 1.16; letter-spacing: -.05em; }
.section-description { max-width: 460px; margin-top: 20px; color: #586171; font-size: 15px; line-height: 1.85; }
.feature-list { display: grid; gap: 24px; margin-block: 32px; }
.feature-list li { display: flex; gap: 14px; }
.feature-list svg { flex-shrink: 0; width: 18px; height: 18px; margin-top: 3px; color: var(--color-brand); }
.feature-list h3 { font-family: var(--font-sans); font-size: 14px; font-weight: 600; }
.feature-list p { max-width: 400px; margin-top: 5px; font-size: 13px; line-height: 1.8; color: #586171; }
.application-preview { background: #f1f4f9; border-radius: 12px; padding: 32px; }
.preview-caption { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; padding-bottom: 30px; font-size: 11px; font-weight: 500; }
.preview-caption span { color: #67707e; font-size: 10px; }
.preview-heading { display: flex; align-items: center; gap: 12px; padding-bottom: 23px; }
.preview-heading svg { color: var(--color-brand); width: 22px; height: 22px; }
.preview-heading h3 { font-size: 22px; font-weight: 500; letter-spacing: -.035em; }
.application-list li { display: flex; align-items: center; justify-content: space-between; gap: 15px; padding-block: 25px; border-top: 1px solid #0a17381a; }
.application-title { font-size: 13px; font-weight: 600; }
.application-detail { margin-top: 6px; color: #67707e; font-size: 11px; }
.application-status { flex-shrink: 0; padding: 5px 9px; border-radius: 5px; font-size: 10px; font-weight: 500; }
.status-review { background: #e2eaf8; color: #345283; }
.status-offer { background: #eee7f8; color: #684398; }
.preview-note { display: flex; align-items: center; gap: 7px; padding-top: 23px; border-top: 1px solid #0a17381a; color: #67707e; font-size: 11px; }
.preview-note svg { width: 15px; height: 15px; }
.company-section { position: relative; overflow: hidden; background: #0a1730; color: #fff; }
.company-background, .company-overlay { position: absolute; inset: 0; width: 100%; height: 100%; }
.company-background { object-fit: cover; object-position: center; }
.company-overlay { background: linear-gradient(90deg, rgb(8 20 43 / .98) 0%, rgb(8 20 43 / .93) 35%, rgb(8 20 43 / .58) 70%, rgb(8 20 43 / .38) 100%); }
.company-content { position: relative; display: flex; justify-content: space-between; align-items: end; gap: 60px; padding-block: 90px; }
.company-copy { max-width: 560px; }
.company-section .landing-label { color: #b9cefa; }
.company-section .section-title { font-size: clamp(36px, 3.8vw, 52px); }
.company-section .section-description, .company-section .feature-list p { color: #c5cddc; }
.company-section .feature-list svg { color: #a5c1ff; }
.company-aside { margin-bottom: 5px; color: #fff; font-family: var(--font-display); font-size: 24px; font-weight: 400; line-height: 1.4; letter-spacing: -.025em; text-align: right; }
.company-section .focus-ring:focus-visible, .closing-section .focus-ring:focus-visible { outline-color: #fff; }
.guides-section { padding-block: 90px; }
.section-heading { display: flex; justify-content: space-between; align-items: end; gap: 30px; }
.section-heading .section-title { max-width: 650px; }
.section-heading > .landing-link { flex-shrink: 0; }
.guide-list { margin-top: 40px; }
.guide-row { display: grid; grid-template-columns: 110px 1fr 90px 24px; align-items: center; gap: 24px; padding-block: 28px; border-top: 1px solid #0a17381a; transition: color 180ms; }
.guide-row:last-child { border-bottom: 1px solid #0a17381a; }
.guide-row:hover h3, .guide-row:hover .guide-arrow { color: var(--color-brand); }
.guide-category { font-size: 11px; font-weight: 600; color: var(--color-brand); }
.guide-row h3 { font-size: 21px; font-weight: 500; line-height: 1.4; letter-spacing: -.025em; }
.guide-row p { max-width: 650px; margin-top: 8px; font-size: 13px; line-height: 1.75; color: #67707e; }
.guide-time { color: #67707e; font-size: 11px; text-align: right; }
.guide-arrow { width: 22px; height: 22px; justify-self: end; }
.faq-section { display: grid; grid-template-columns: .85fr 1.15fr; gap: 80px; padding-block: 0 90px; }
.faq-section .section-description { margin-block: 22px 6px; }
.faq-list { border-top: 1px solid #0a17381a; }
.faq-item { border-bottom: 1px solid #0a17381a; }
.faq-item summary { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding-block: 22px; cursor: pointer; font-size: 14px; font-weight: 500; list-style: none; }
.faq-item summary::-webkit-details-marker { display: none; }
.faq-toggle { flex-shrink: 0; width: 18px; height: 18px; color: #67707e; transition: transform 180ms; }
.faq-item[open] .faq-toggle { transform: rotate(45deg); }
.faq-item > p { max-width: 550px; padding: 0 25px 24px 0; font-size: 13px; line-height: 1.85; color: #586171; }
.closing-section { background: var(--color-brand); color: #fff; }
.closing-content { display: flex; align-items: center; justify-content: space-between; gap: 40px; padding-block: 65px; }
.closing-section .landing-label { color: #d3e1ff; }
.closing-section .section-title { max-width: 660px; margin-top: 14px; }
.closing-content > div > p:last-child { margin-top: 12px; color: #d3e1ff; font-size: 18px; }
.closing-actions { display: flex; flex-shrink: 0; flex-direction: column; align-items: center; gap: 16px; }
.closing-actions .landing-link { font-size: 12px; }
.closing-actions .link-label { text-decoration-color: #ffffff60; }
.closing-actions .landing-link:hover { color: #fff; }
.closing-actions .landing-link:hover .link-label { text-decoration-color: #fff; }
@media (max-width: 1023px) {
	.hero { padding-block: 55px; }
	.hero h1 { font-size: clamp(48px, 6.4vw, 68px); }
	.hero-description { font-size: 15px; }
	.hero-actions { gap: 16px; }
	.hero-actions .landing-link { font-size: 12px; }
	.steps { gap: 24px; }
	.student-grid { gap: 45px; }
	.application-preview { padding: 24px; }
	.application-list li { flex-wrap: wrap; gap: 10px; padding-block: 20px; }
	.company-aside { max-width: 220px; font-size: 20px; }
	.faq-section { gap: 45px; }
	.guide-row { grid-template-columns: 85px 1fr 75px 20px; gap: 18px; }
}
@media (max-width: 767px) {
	.hero { grid-template-columns: 1fr; gap: 25px; padding-block: 48px 35px; }
	.hero h1 { font-size: clamp(46px, 10.5vw, 74px); }
	.hero-description { max-width: 510px; }
	.hero-art { width: min(100%, 420px); justify-self: center; }
	.hero-art figcaption { margin-top: -5px; }
	.how-section { padding-block: 28px 40px; }
	.how-heading { align-items: start; flex-direction: column; gap: 12px; }
	.how-heading h2 { font-size: 20px; }
	.steps { grid-template-columns: 1fr; gap: 28px; margin-top: 30px; }
	.steps li { gap: 20px; }
	.steps p { max-width: 450px; }
	.student-section { padding-block: 60px; }
	.student-grid { grid-template-columns: 1fr; gap: 35px; }
	.application-preview { padding: 25px; }
	.preview-caption { padding-bottom: 25px; }
	.application-list li { flex-wrap: nowrap; }
	.company-content { padding-block: 65px; }
	.company-overlay { background: rgb(8 20 43 / .88); }
	.company-background { object-position: 65% center; }
	.company-aside { display: none; }
	.guides-section { padding-block: 60px; }
	.section-heading { flex-direction: column; align-items: start; gap: 20px; }
	.guide-list { margin-top: 30px; }
	.guide-row { grid-template-columns: 1fr auto; gap: 12px 20px; padding-block: 24px; }
	.guide-category { grid-column: 1; }
	.guide-row > div { grid-column: 1; grid-row: 2; }
	.guide-row h3 { font-size: 20px; }
	.guide-time { grid-column: 2; grid-row: 1; }
	.guide-arrow { grid-column: 2; grid-row: 2; align-self: start; }
	.faq-section { grid-template-columns: 1fr; gap: 30px; padding-bottom: 60px; }
	.closing-content { flex-direction: column; align-items: start; gap: 30px; padding-block: 50px; }
	.closing-actions { align-items: start; }
}
@media (prefers-reduced-motion: reduce) {
	.faq-toggle { transition: none; }
}
</style>
