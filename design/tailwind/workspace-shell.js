/* ===========================================================
   Internly — Tailwind workspace shell
   <app-sidebar active="dashboard" role="student"></app-sidebar>
   <app-topbar></app-topbar>
   Renders Tailwind-utility markup directly (no workspace.css).
   =========================================================== */
(function () {
  const I = {
    dashboard: '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="3" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="14" y="12" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="2"/><rect x="3" y="16" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="2"/></svg>',
    applications: '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    internships: '<svg viewBox="0 0 24 24" fill="none"><rect x="3" y="7" width="18" height="13" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2" stroke="currentColor" stroke-width="2"/></svg>',
    saved: '<svg viewBox="0 0 24 24" fill="none"><path d="M6 4h12v16l-6-4-6 4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    messages: '<svg viewBox="0 0 24 24" fill="none"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    companies: '<svg viewBox="0 0 24 24" fill="none"><path d="M3 21V8l7-5 7 5v13M9 21v-6h2v6M3 21h18" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    profile: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    settings: '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/><path d="M19 12a7 7 0 00-.1-1l2-1.5-2-3.5-2.4 1a7 7 0 00-1.7-1l-.4-2.5h-4l-.4 2.5a7 7 0 00-1.7 1l-2.4-1-2 3.5L4 11a7 7 0 000 2l-2 1.5 2 3.5 2.4-1a7 7 0 001.7 1l.4 2.5h4l.4-2.5a7 7 0 001.7-1l2.4 1 2-3.5-2-1.5a7 7 0 00.1-1z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>',
    logo: '<svg viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    logout: '<svg viewBox="0 0 24 24" fill="none"><path d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 17l-5-5 5-5M5 12h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    search: '<svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
    bell: '<svg viewBox="0 0 24 24" fill="none"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  };

  const ROLES = {
    student: {
      tag: 'STUDENT WORKSPACE',
      person: { name: 'Ala Baganne', initials: 'AB', role: 'Student' },
      action: { label: 'Find internships', href: 'browse.html' },
      searchPlaceholder: 'Search internships, companies…',
      groups: [
        { label: 'Workspace', items: [
          { id: 'dashboard',    label: 'Dashboard',    href: 'dashboard.html' },
          { id: 'applications', label: 'Applications', href: 'applications.html', badge: 6 },
          { id: 'internships',  label: 'Internships',  href: 'internships.html' },
          { id: 'saved',        label: 'Saved roles',  href: 'saved.html', badge: 5 },
          { id: 'messages',     label: 'Messages',     href: 'messages.html', badge: 3 },
          { id: 'companies',    label: 'Companies',    href: 'companies.html' }
        ]},
        { label: 'Account', items: [
          { id: 'profile',  label: 'Profile',  href: 'profile.html' },
          { id: 'settings', label: 'Settings', href: 'settings.html' }
        ]}
      ]
    }
  };

  const linkBase = 'flex items-center gap-3 px-[13px] py-[11px] rounded-[11px] text-[14.5px] font-medium transition-colors';
  const linkOff = 'text-[#aeb9cf] hover:bg-white/[.06] hover:text-white';
  const linkOn = 'bg-blue-600 text-white';

  function navMarkup(role, active) {
    const cfg = ROLES[role] || ROLES.student;
    let html = `<aside class="flex flex-col sticky top-0 h-screen bg-navy-900 text-[#aeb9cf] px-4 py-[22px]">
      <a class="flex items-center gap-[11px] px-2 pt-1.5 pb-[26px]" href="index.html">
        <span class="w-[38px] h-[38px] rounded-[11px] grid place-items-center text-white bg-gradient-to-br from-blue-400 to-blue-700 [&_svg]:w-[21px] [&_svg]:h-[21px]">${I.logo}</span>
        <span><b class="block font-display text-[19px] font-semibold tracking-tight text-white">Internly</b><small class="block text-[10px] tracking-[.16em] text-[#7c89a3] font-semibold -mt-0.5">${cfg.tag}</small></span>
      </a>`;
    cfg.groups.forEach(g => {
      html += `<div class="text-[11px] tracking-[.13em] uppercase text-[#5d6a85] font-bold px-3 pt-[18px] pb-2">${g.label}</div><nav class="flex flex-col gap-[3px]">`;
      g.items.forEach(it => {
        const cls = `${linkBase} ${it.id === active ? linkOn : linkOff} [&_svg]:w-[19px] [&_svg]:h-[19px] [&_svg]:shrink-0`;
        const badge = it.badge
          ? ` <span class="ml-auto text-[11px] font-bold ${it.id === active ? 'bg-white/25' : 'bg-blue-600'} text-white min-w-[20px] h-5 rounded-full grid place-items-center px-1.5">${it.badge}</span>`
          : '';
        html += `<a class="${cls}" href="${it.href}">${I[it.id] || ''} <span>${it.label}</span>${badge}</a>`;
      });
      html += `</nav>`;
    });
    const p = cfg.person;
    html += `<div class="flex-1"></div>
      <div class="flex items-center gap-[11px] px-3 py-[11px] rounded-[12px] bg-white/5 mt-2">
        <span class="w-9 h-9 rounded-full grid place-items-center text-white font-bold font-display text-sm bg-gradient-to-br from-blue-300 to-blue-700 shrink-0">${p.initials}</span>
        <div class="min-w-0"><b class="block text-white text-[13.5px] truncate">${p.name}</b><span class="text-xs text-[#7c89a3]">${p.role}</span></div>
        <button class="ml-auto w-[34px] h-[34px] rounded-[9px] grid place-items-center text-[#7c89a3] bg-transparent border-0 cursor-pointer transition-colors hover:bg-red-500/[.16] hover:text-red-300 [&_svg]:w-[18px] [&_svg]:h-[18px]" type="button" title="Log out" aria-label="Log out" onclick="location.href='login.html'">${I.logout}</button>
      </div>
    </aside>`;
    return html;
  }

  function topbarMarkup(role) {
    const cfg = ROLES[role] || ROLES.student;
    return `<header class="h-[70px] bg-white border-b border-line flex items-center justify-between gap-[18px] px-8 max-[680px]:px-[18px] sticky top-0 z-20">
      <div class="flex items-center gap-2 flex-1 min-w-0 max-w-[520px]">
        <label class="flex items-center gap-2.5 bg-paper-2 border border-line rounded-[11px] px-3.5 h-[42px] flex-1 min-w-0 focus-within:border-blue-400 [&_svg]:w-[17px] [&_svg]:h-[17px] [&_svg]:text-muted-2 [&_svg]:shrink-0">${I.search}<input class="border-0 bg-transparent outline-none text-sm w-full" type="text" placeholder="${cfg.searchPlaceholder}"></label>
        <button class="inline-flex items-center justify-center font-semibold text-[15px] px-5 h-[42px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors whitespace-nowrap max-[680px]:hidden" type="button">Search</button>
      </div>
      <div class="flex items-center gap-2.5 shrink-0">
        <a class="inline-flex items-center justify-center font-semibold text-[15px] px-[22px] h-[42px] rounded-[12px] bg-blue-600 text-white hover:bg-blue-700 transition-colors whitespace-nowrap" href="${cfg.action.href}">${cfg.action.label}</a>
        <a class="w-[42px] h-[42px] rounded-[11px] border border-line bg-white grid place-items-center text-ink-700 hover:border-blue-300 hover:text-blue-700 transition-colors [&_svg]:w-[19px] [&_svg]:h-[19px]" href="messages.html" title="Messages" aria-label="Messages">${I.messages}</a>
        <button class="w-[42px] h-[42px] rounded-[11px] border border-line bg-white grid place-items-center text-ink-700 hover:border-blue-300 hover:text-blue-700 transition-colors relative [&_svg]:w-[19px] [&_svg]:h-[19px]" type="button" title="Notifications" aria-label="Notifications"><span class="absolute top-2 right-[9px] w-2 h-2 rounded-full bg-red-500 border-2 border-white"></span>${I.bell}</button>
      </div>
    </header>`;
  }

  class AppSidebar extends HTMLElement {
    connectedCallback() {
      this.style.display = 'contents';
      this.innerHTML = navMarkup(this.getAttribute('role') || 'student',
                                 this.getAttribute('active') || '');
    }
  }
  class AppTopbar extends HTMLElement {
    connectedCallback() {
      this.style.display = 'contents';
      this.innerHTML = topbarMarkup(this.getAttribute('role') || 'student');
    }
  }

  /* ---------------------------------------------------------
     <app-shell active="dashboard">  ...page content...  </app-shell>
     A {children}-style layout wrapper: it takes whatever you
     nest inside it as the page body, and renders the sidebar +
     topbar chrome around it. Add `fill` for full-height,
     internally-scrolling pages (e.g. messages).
     --------------------------------------------------------- */
  class AppShell extends HTMLElement {
    connectedCallback() {
      const active = this.getAttribute('active') || '';
      const role = this.getAttribute('role') || 'student';
      const fill = this.hasAttribute('fill');
      const children = this.innerHTML;            // the page content
      const outerH = fill ? 'h-screen' : 'min-h-screen';
      const colCls = fill ? 'flex flex-col min-w-0 overflow-hidden' : 'flex flex-col min-w-0';
      this.style.display = 'block';
      this.innerHTML =
        `<div class="grid grid-cols-[248px_1fr] ${outerH}">` +
          `<app-sidebar active="${active}" role="${role}"></app-sidebar>` +
          `<div class="${colCls}">` +
            `<app-topbar role="${role}"></app-topbar>` +
            children +
          `</div>` +
        `</div>`;
    }
  }

  customElements.define('app-sidebar', AppSidebar);
  customElements.define('app-topbar', AppTopbar);
  customElements.define('app-shell', AppShell);
})();
