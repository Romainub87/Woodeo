/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./assets/**/*.js",
        "./templates/**/*.html.twig",
    ],
    theme: {
        extend: {
            colors: {
                "blue-custom": "var(--color-custom)"
            }
           
        },
    },
    plugins: [],
}