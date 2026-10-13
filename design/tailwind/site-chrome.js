/* ===========================================================
   Internly — Tailwind marketing chrome
   <site-header active="about"></site-header>
   <site-footer></site-footer>
   Renders Tailwind-utility markup (no styles.css).
   =========================================================== */
(function () {
  const logo = '<svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  const NAV = [
    { id: 'browse',  label: 'Browse internships', href: 'browse.html' },
    { id: 'blog',    label: 'Blog',               href: 'blog.html' },
    { id: 'about',   label: 'About',              href: 'about.html' },
    { id: 'contact', label: 'Contact',            href: 'contact.html' },
  ];

  function headerMarkup(active, solid) {
    const bg = solid ? 'bg-white' : 'bg-white/[.82] backdrop-blur-[14px]';
    const links = NAV.map(n => {
      const on = n.id === active ? 'text-blue-700' : 'text-ink-700';
      return `<a class="text-[14.5px] font-medium ${on} hover:text-blue-700 transition-colors" href="${n.href}">${n.label}</a>`;
    }).join('');
    return `<header class="sticky top-0 z-[60] ${bg} border-b border-line">
      <div class="max-w-wrap mx-auto px-7 flex items-center justify-between h-[74px]">
        <a class="flex items-center gap-[11px]" href="index.html">
          <span class="w-[38px] h-[38px] rounded-[11px] bg-gradient-to-br from-blue-400 to-blue-700 grid place-items-center text-white shadow-[0_8px_18px_-8px_rgba(37,99,235,.8)] [&_svg]:w-[21px] [&_svg]:h-[21px]">${logo}</span>
          <span><b class="block font-display text-[19px] font-semibold tracking-tight">Internly</b><small class="block text-[10px] tracking-[.16em] text-muted-2 font-semibold -mt-0.5">INTERNSHIP PLATFORM</small></span>
        </a>
        <nav class="flex items-center gap-[34px] max-[920px]:hidden">${links}</nav>
        <div class="flex items-center gap-3.5">
          <a class="text-[14.5px] font-semibold text-ink max-[920px]:hidden" href="login.html">Log in</a>
          <a class="inline-flex items-center gap-2.5 font-semibold text-[15px] px-[22px] py-[13px] rounded-[12px] bg-blue-600 text-white shadow-cta hover:bg-blue-700 hover:-translate-y-px transition-all" href="auth.html">Create account</a>
        </div>
      </div>
    </header>`;
  }

  function footerMarkup() {
    const col = 'class="[&_a]:block [&_a]:text-[#9aa6bd] [&_a]:text-sm [&_a]:py-1.5 [&_a:hover]:text-white"';
    const h5 = 'class="font-display text-white text-sm tracking-[.04em] mb-[18px] font-semibold"';
    return `<footer class="bg-navy-900 text-[#aeb9cf] pt-[78px] pb-[34px]">
      <div class="max-w-wrap mx-auto px-7">
        <div class="grid grid-cols-[1.6fr_1fr_1fr_1fr] gap-10 max-[820px]:grid-cols-2 max-[820px]:gap-8">
          <div>
            <a class="flex items-center gap-[11px] mb-[18px]" href="index.html">
              <span class="w-[38px] h-[38px] rounded-[11px] bg-gradient-to-br from-blue-400 to-blue-700 grid place-items-center text-white [&_svg]:w-[21px] [&_svg]:h-[21px]">${logo}</span>
              <span><b class="block font-display text-[19px] font-semibold tracking-tight text-white">Internly</b><small class="block text-[10px] tracking-[.16em] text-[#7c89a3] font-semibold -mt-0.5">INTERNSHIP PLATFORM</small></span>
            </a>
            <p class="max-w-[300px] text-sm leading-[1.6] text-[#9aa6bd]">Where ambitious students and great companies find each other. Discover, apply, and get hired — all in one place.</p>
            <div class="flex gap-2.5 mt-[22px] [&_a]:w-9 [&_a]:h-9 [&_a]:rounded-[9px] [&_a]:bg-white/[.06] [&_a]:grid [&_a]:place-items-center [&_a:hover]:bg-blue-600 [&_svg]:w-[17px] [&_svg]:h-[17px]">
              <a href="#"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 5.8a8 8 0 01-2.4.7 4 4 0 001.8-2.2c-.8.5-1.7.8-2.6 1a4 4 0 00-6.8 3.6A11.4 11.4 0 013 4.8a4 4 0 001.2 5.3c-.6 0-1.2-.2-1.7-.5a4 4 0 003.2 4 4 4 0 01-1.8.1 4 4 0 003.7 2.8A8 8 0 012 18.3a11.3 11.3 0 006.1 1.8c7.4 0 11.5-6.2 11.5-11.5v-.5A8 8 0 0022 5.8z"/></svg></a>
              <a href="#"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zM8.3 18.3H5.7V10h2.6v8.3zM7 8.8a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm11.3 9.5h-2.6v-4c0-1-.4-1.7-1.3-1.7-.7 0-1.1.5-1.3 1-.1.2-.1.4-.1.7v4h-2.6V10h2.6v1.1c.3-.5 1-1.3 2.4-1.3 1.7 0 3 1.2 3 3.6v4.9z"/></svg></a>
              <a href="#"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 00-3.2 19.5c.5.1.7-.2.7-.5v-1.7c-2.8.6-3.4-1.3-3.4-1.3-.5-1.2-1.1-1.5-1.1-1.5-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.5 2.3 1.1 2.9.8.1-.6.3-1.1.6-1.3-2.2-.3-4.6-1.1-4.6-5 0-1.1.4-2 1-2.7-.1-.3-.4-1.3.1-2.6 0 0 .8-.3 2.7 1a9.3 9.3 0 015 0c1.9-1.3 2.7-1 2.7-1 .5 1.3.2 2.3.1 2.6.6.7 1 1.6 1 2.7 0 3.9-2.3 4.7-4.6 5 .4.3.7.9.7 1.8v2.7c0 .3.2.6.7.5A10 10 0 0012 2z"/></svg></a>
            </div>
          </div>
          <div ${col}>
            <h5 ${h5}>For students</h5>
            <a href="browse.html">Browse internships</a>
            <a href="auth.html">Create profile</a>
            <a href="#">Career resources</a>
            <a href="blog.html">Blog</a>
          </div>
          <div ${col}>
            <h5 ${h5}>For companies</h5>
            <a href="#">Post a role</a>
            <a href="#">Pricing</a>
            <a href="#">Hiring guide</a>
            <a href="#">Book a demo</a>
          </div>
          <div ${col}>
            <h5 ${h5}>Company</h5>
            <a href="about.html">About</a>
            <a href="contact.html">Contact</a>
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
          </div>
        </div>
        <div class="flex justify-between items-center mt-[54px] pt-[26px] border-t border-white/[.09] text-[13px] text-[#7c89a3] max-[820px]:flex-col max-[820px]:gap-3.5">
          <span>© 2026 Internly. All rights reserved.</span>
          <span>Made for students, by people who remember being one.</span>
        </div>
      </div>
    </footer>`;
  }

  class SiteHeader extends HTMLElement {
    connectedCallback() {
      this.style.display = 'block';
      this.innerHTML = headerMarkup(this.getAttribute('active') || '', this.hasAttribute('solid'));
    }
  }
  class SiteFooter extends HTMLElement {
    connectedCallback() {
      this.style.display = 'block';
      this.innerHTML = footerMarkup();
    }
  }
  customElements.define('site-header', SiteHeader);
  customElements.define('site-footer', SiteFooter);

  // shared reveal animation (pure Tailwind class toggling)
  function initReveals() {
    const reveals = document.querySelectorAll('.reveal');
    const show = el => { el.classList.add('in'); el.style.opacity = '1'; el.style.transform = 'none'; };
    const showAll = () => reveals.forEach(show);
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((es) => {
        es.forEach(e => { if (e.isIntersecting) { show(e.target); io.unobserve(e.target); } });
      }, { threshold: .08, rootMargin: '0px 0px -5% 0px' });
      reveals.forEach((el, i) => { el.style.transitionDelay = (i % 3 * 70) + 'ms'; io.observe(el); });
      requestAnimationFrame(() => reveals.forEach(el => { const r = el.getBoundingClientRect(); if (r.top < innerHeight && r.bottom > 0) show(el); }));
      setTimeout(showAll, 1200);
    } else { showAll(); }
  }
  if (document.readyState !== 'loading') initReveals();
  else document.addEventListener('DOMContentLoaded', initReveals);
})();
