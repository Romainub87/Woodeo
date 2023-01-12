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