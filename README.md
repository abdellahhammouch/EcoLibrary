# EcoLibrary API

API REST developpee avec Laravel pour gerer une bibliotheque ecologique.

## Apercu

Le projet couvre les fonctionnalites suivantes :

- Authentification avec Laravel Sanctum
- Gestion des categories
- Gestion des livres
- Gestion des exemplaires
- Statistiques administrateur
- Suivi des exemplaires degrades
- Slugs pour categories et livres

## Roles

- `admin` : acces complet
- `lecteur` : consultation uniquement

## Documentation Postman

- Documentation publiee : https://go.postman.co/collection/53114106-0a719fc9-640c-4b25-ab2f-6c4163b6dcf7?source=collection_link
- Collection exportee dans le projet : `EcoLibrary API.postman_collection.json`

## Prerequis

- PHP `^8.2`
- Composer
- MySQL
- Node.js et npm

## Installation

1. Cloner le projet.
2. Installer les dependances PHP :

```bash
composer install
```

3. Copier le fichier d'environnement :

```bash
cp .env.example .env
```

4. Generer la cle de l'application :

```bash
php artisan key:generate
```

5. Configurer la connexion MySQL dans `.env`.

## Initialisation de la base

Pour repartir d'une base propre avec des donnees de test coherentes :

```bash
php artisan migrate:fresh --seed
```

Le seeder principal cree :

- `1` administrateur
- `1` lecteur principal
- `8` lecteurs supplementaires
- `5` categories
- `4` ou `5` livres par categorie
- `2` ou `3` exemplaires par livre

## Comptes de test

- `admin@ecolibrary.test` / `password`
- `reader@ecolibrary.test` / `password`

## Lancer le projet

Lancer l'API localement :

```bash
php artisan serve
```

URL locale par defaut :

```txt
http://127.0.0.1:8000
```

## Utilisation avec Postman

1. Importer la collection `EcoLibrary API.postman_collection.json`.
2. Creer un environnement Postman local, ou reutiliser celui deja exporte si tu en as un.
3. Definir au minimum les variables suivantes :

```txt
base_url = http://127.0.0.1:8000/api
token =
category_slug = biodiversite
book_slug = guide-du-compost-maison
book_id = 1
copy_id = 1
admin_email = admin@ecolibrary.test
admin_password = password
reader_email = reader@ecolibrary.test
reader_password = password
```

4. Executer `Login` pour recuperer automatiquement le `token`.
5. Tester ensuite les routes protegees avec `Bearer {{token}}`.

## Ordre de test recommande

1. `POST /register`
2. `POST /login`
3. `GET /me`
4. `GET /categories`
5. `GET /books`
6. `GET /books/search`
7. `GET /categories/{category_slug}/books`
8. Routes admin : categories, books, book-copies, stats

## Authentification

L'API utilise des tokens Sanctum.

- Routes publiques : `POST /register`, `POST /login`
- Routes authentifiees : toutes les autres
- Routes admin : creation, modification, suppression, statistiques

Le token doit etre envoye dans l'en-tete :

```txt
Authorization: Bearer <token>
```

## Endpoints principaux

### Auth

- `POST /api/register`
- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

### Categories

- `GET /api/categories`
- `GET /api/categories/{category_slug}`
- `POST /api/categories`
- `PUT /api/categories/{category_slug}`
- `DELETE /api/categories/{category_slug}`

### Books

- `GET /api/books`
- `GET /api/books/search`
- `GET /api/books/{book_slug}`
- `POST /api/books`
- `PUT /api/books/{book_slug}`
- `DELETE /api/books/{book_slug}`
- `GET /api/categories/{category_slug}/books`

### Book Copies

- `GET /api/book-copies`
- `POST /api/book-copies`
- `PUT /api/book-copies/{copy_id}`
- `DELETE /api/book-copies/{copy_id}`

### Admin

- `GET /api/admin/stats`

## Tests

Executer la suite de tests :

```bash
php artisan test
```

## Notes

- Les slugs sont utilises pour les categories et les livres.
- La consultation d'un livre incremente son compteur de consultations.
- Une notification email est envoyee lorsqu'un exemplaire est marque comme degrade.
