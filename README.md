# ETAP - Suivi de consommation

Application web PHP de gestion et de suivi de la consommation d'electricite, de gaz et d'eau a partir des compteurs et des factures.

## Fonctionnalites

- Authentification et inscription des utilisateurs
- Tableau de bord avec les totaux de consommation
- Gestion des regions, locaux et groupes
- Gestion des familles et sous-familles
- Gestion des compteurs
- Recherche d'un compteur
- Enregistrement et consultation des factures
- Consultation des etats de consommation
- Generation d'un QR code de demonstration

## Prerequis

- Windows avec XAMPP ou XAMPP Lite
- Apache
- PHP avec l'extension `mysqli`
- MySQL ou MariaDB
- Un navigateur web

## Installation locale

1. Cloner le depot dans le dossier `htdocs` de XAMPP :

   ```bash
   git clone https://github.com/eyaarbi62-beep/ETAP-Gst-consommation.git ETAP
   ```

2. Demarrer Apache et MySQL depuis le panneau de controle XAMPP.

3. Creer une base de donnees MySQL nommee `etap_consommation`.

4. Importer le schema et les donnees initiales de la base de donnees, s'ils sont disponibles.
   Aucun fichier SQL n'est actuellement fourni dans ce depot.

5. Verifier la configuration de la connexion dans `config.php` :

   ```php
   $host = "localhost";
   $user = "root";
   $password = "";
   $dbname = "etap_consommation";
   ```

6. Ouvrir l'application dans le navigateur :

   ```text
   http://localhost/ETAP/login.php
   ```

## Structure principale

| Fichier | Role |
| --- | --- |
| `login.php` | Connexion des utilisateurs |
| `inscription.php` | Creation d'un compte |
| `dashboard.php` | Tableau de bord |
| `compteurs.php` | Gestion des compteurs |
| `chercher_compteur.php` | Recherche de compteurs |
| `factures.php` | Gestion des factures |
| `etats.php` | Etats de consommation |
| `locaux.php` | Gestion des locaux |
| `regions.php` | Gestion des regions |
| `groupes.php` | Gestion des groupes |
| `familles.php` | Gestion des familles |
| `sous_familles.php` | Gestion des sous-familles |
| `utilisateurs.php` | Gestion des utilisateurs |
| `config.php` | Connexion a la base de donnees |
| `style.css` | Styles communs |

## Developpement

Les fichiers PHP sont servis directement par Apache. Apres une modification, recharger la page dans le navigateur et consulter les journaux Apache en cas d'erreur.

## Securite

Avant un deploiement en production :

- definir un mot de passe MySQL et ne pas le stocker en clair dans le code ;
- stocker les mots de passe utilisateurs avec `password_hash()` et les verifier avec `password_verify()` ;
- utiliser des variables d'environnement pour les secrets ;
- activer HTTPS ;
- verifier les autorisations d'acces a chaque page.

## Licence

Aucune licence n'est actuellement declaree pour ce projet.
