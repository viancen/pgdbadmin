/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./views/**/*.ejs', './public/**/*.js'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['JetBrains Mono', 'ui-monospace', 'monospace'],
        display: ['Space Grotesk', 'system-ui', 'sans-serif'],
      },
      colors: {
        pg: {
          dark: '#0f172a',
          panel: '#1e293b',
          border: '#334155',
          accent: '#38bdf8',
          muted: '#64748b',
          success: '#22c55e',
          warning: '#eab308',
          danger: '#ef4444',
        },
      },
      boxShadow: {
        glow: '0 0 20px -5px rgba(56, 189, 248, 0.3)',
        'glow-sm': '0 0 10px -3px rgba(56, 189, 248, 0.2)',
      },
    },
  },
  plugins: [],
};
