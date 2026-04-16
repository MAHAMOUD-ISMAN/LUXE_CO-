# LUXE.CO

Plateforme e-commerce de luxe développée dans le cadre du module Systèmes de Recommandation à l'Université de Djibouti (IUT-T), année 2025/2026.

Le projet existe en deux versions. Les deux sites sont identiques visuellement, la seule différence c'est le moteur de recommandation utilisé.

---

## Les deux versions

**Version 1 — Filtrage Collaboratif**
http://boutiqueluxe.rf.gd

**Version 2 — Filtrage par Contenu**
https://luxecontent123.rf.gd

---

## L'équipe

| N° | Nom | Rôle |
|----|-----|------|
| 1 | Mahamoud Isman Robleh | Chef de projet & Architecture |
| 2 | Ali Djama Hamad | Algorithme contenu & Vecteurs features |
| 3 | Hassan Mahamoud Hassan | Algorithme collaboratif & Base de données |
| 4 | Mahdi Said Ibrahim | Intégration Stripe & Sécurité |
| 5 | Abdoulrazak Aden Abdillahi | Frontend & Design UI |
| 6 | Loukman Youssouf Miad | Authentification OTP & Backend |
| 7 | Assoweh Osman Aganeh | Déploiement & Tests |
| 8 | Houssein Ahmed Abdillahi | Documentation & Rapport |

---

## Comment utiliser le site

### 1. Se connecter

En haut du formulaire de connexion, vous trouverez des comptes déjà prêts à utiliser. Pour que vous ne perdez pas assez de votre temps en avec la creation chere professeur .

| Type | Email | Mot de passe |
|------|-------|-------------|
| Utilisateur normal | alex@demo.com | demo123 |
| Administrateur | admin@demo.com | admin123 |

Le compte admin donne accès à un panneau d'administration séparé pour gérer les produits, les utilisateurs et consulter les statistiques.

### 2. Naviguer et générer des recommandations

Allez sur le catalogue et consultez quelques fiches produit. Chaque consultation est enregistrée automatiquement en base de données. Ensuite, rendez-vous sur "Mon Compte" pour voir les recommandations générées en temps réel. Plus vous consultez de produits, plus les suggestions deviennent pertinentes.

### 3. Ajouter au panier

Ajoutez des articles depuis les fiches produit. La livraison est gratuite au-dessus de 500 euros. Le stock est vérifié côté serveur avant chaque ajout.

### 4. Passer commande

**Important : n'utilisez pas votre vraie carte bancaire.** Le site fonctionne en mode test Stripe. Si vous entrez une vraie carte, le montant sera débité pour de vrai et ne sera pas remboursable.

Utilisez uniquement les cartes de test affichées en haut du formulaire de paiement :

| Carte | Numéro | Résultat |
|-------|--------|----------|
| Visa | 4242 4242 4242 4242 | Paiement accepté |
| Mastercard | 5555 5555 5555 4444 | Paiement accepté |
| Refus | 4000 0000 0000 0002 | Paiement refusé |
| 3D Secure | 4000 0025 0000 3155 | Authentification 3D |

Pour l'expiration et le CVC, mettez n'importe quelle date future et n'importe quel code à 3 chiffres.

---

## Ce qui change entre les deux versions

Les deux sites ont exactement le même code, sauf le fichier `recommend.php` qui contient le moteur de recommandation de filtrage collaboratif et `recommendation_filtrage_contenu.php` qui contient le moteur de recommandation de filtrage de contenu.

**Version 1 (Collaboratif)** : le système regarde ce que des utilisateurs similaires ont consulté, et recommande en fonction de ça. Il a besoin d'un minimum d'utilisateurs actifs pour bien fonctionner.

**Version 2 (Contenu)** : le système regarde les caractéristiques des produits eux-mêmes (catégorie, prix, note...) et construit un profil de goût pour chaque utilisateur. Il fonctionne bien dès la première consultation.

---

## Stack technique

- Backend : PHP 8 sans framework
- Base de données : MySQL 8
- Paiement : Stripe API v3 en mode test
- Email / OTP : Gmail SMTP SSL port 465
- Hébergement : InfinityFree
- SSL : Cloudflare

---

*Université de Djibouti — IUT-T — Avril 2026*
