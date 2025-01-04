/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.{html,js,php,twig}",
    "./src/**/*.{html,js,php,twig}",
  ],
  darkMode: "selector",
  theme: {
    extend: {},
  },
  plugins: [],
}
