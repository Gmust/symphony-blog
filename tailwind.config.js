/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.{html,js,php,twig}",
    "./src/**/*.{html,js,php,twig}",
  ],
  darkMode: "class", // or 'media' if you prefer that method
  theme: {
    extend: {},
  },
  plugins: [],
}
