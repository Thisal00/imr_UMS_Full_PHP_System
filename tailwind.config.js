/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./**/*.php",
    "./assets/js/**/*.js"
  ],
  corePlugins: {
    preflight: false
  },
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#f5f8ff',
          100: '#e8efff',
          200: '#cddcff',
          300: '#a4beff',
          400: '#7897ff',
          500: '#4f6eff',
          600: '#394fe6',
          700: '#2d3db4',
          800: '#26348f',
          900: '#232f75'
        }
      }
    }
  },
  plugins: []
}
