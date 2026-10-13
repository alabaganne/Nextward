/* ===========================================================
   Internly — Tailwind (Play CDN) shared config
   Load order in <head>:
     <link rel="preconnect" ...> + Google Fonts <link>
     <script src="https://cdn.tailwindcss.com"></script>
     <script src="tw-setup.js"></script>
   Maps the original styles.css design tokens into Tailwind's
   theme so utility classes (bg-blue-600, font-display, etc.)
   produce the exact same palette / type / radii as the
   hand-written CSS version.
   =========================================================== */
tailwind.config = {
  theme: {
    extend: {
      colors: {
        blue: {
          50:  '#eef4ff',
          100: '#dbe7ff',
          200: '#b9d0ff',
          300: '#8ab2ff',
          400: '#5a8dff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
        navy: {
          700: '#0f2150',
          800: '#0a1738',
          900: '#070f26',
        },
        ink: {
          DEFAULT: '#0c1322',
          700: '#283449',
        },
        muted: {
          DEFAULT: '#5b6677',
          2: '#8a93a3',
        },
        line: {
          DEFAULT: '#e7ebf1',
          2: '#eef1f6',
        },
        paper: {
          DEFAULT: '#ffffff',
          2: '#f6f8fc',
          3: '#eef2f9',
        },
      },
      fontFamily: {
        display: ['"Space Grotesk"', 'system-ui', 'sans-serif'],
        body: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
      },
      borderRadius: {
        sm2: '12px',
        DEFAULT: '18px',
        xl2: '18px',
        '2xl2': '26px',
      },
      maxWidth: {
        wrap: '1200px',
      },
      boxShadow: {
        xs: '0 1px 2px rgba(12,19,34,.05)',
        card: '0 18px 40px -22px rgba(15,33,80,.28)',
        big: '0 40px 90px -45px rgba(15,33,80,.45)',
        cta: '0 14px 26px -14px rgba(37,99,235,.85)',
      },
      letterSpacing: {
        eyebrow: '.18em',
      },
    },
  },
};
