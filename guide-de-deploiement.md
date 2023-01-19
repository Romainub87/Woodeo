# Guide de déploiement

## Passer un compte administrateur depuis la base de données

1. Crée un nouveau compte depuis le site web.
2. Ouvrer un gestionnaire de bases de données comme phpMyAdmin.
3. Écrivez la requête suivante en remplacent l'email par celui de votre compte récemment crée.
```SQL
UPDATE user SET admin = 1 WHERE email LIKE 'votre email ici'
```

## Passer un compte administrateur depuis un autre sur le site

1. Crée un nouveau compte depuis le site web.
2. Connectez-vous avec un compte administrateur sur le site.
3. Appuyer sur le lien en haut à droite `Tableau de bord`
4. Recherche le compte récemment crée.
5. Appuyer sur promouvoir en face du compte.

## Lancer Tailwind
1. Ouvrir un terminal dans le dossier "symfony" du projet
2. Tapez "composer require symfony/webpack-encore-bundle"
3. Tapez ensuite "npm install -D tailwindcss postcss autoprefixer postcss-loader" puis 
"npx tailwindcss init -p"
4. Dans le fichier "webpack.config.js" décommentez la ligne avec '.enablePostCssLoader' ou sinon l'écrire.
5. Dans le fichier "tailwind.config.js" écrire
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
6. Dans le fichier ".assets/styles/app.css" ajoutez 
@tailwind base;
@tailwind components;
@tailwind utilities; 
7. Taper la commande "npm run watch"
8. Et c'est bon !!
