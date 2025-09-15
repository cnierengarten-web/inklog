# Inklog 🖋️

Blog prototype built with **Symfony 7**.
Goal: explore a modern, testable architecture (CRUD, upload, API) within a simple but complete project.

---

## 🚀 Features

* **Articles, categories & Tags**

    * Create / edit / delete articles
    * Create / edit / delete categories and tags
    * Associate tags and categories
    * Automatic slugs

* **Security**
  * Public read access
  * Admin dashboard restricted to `ROLE_ADMIN`
  * User management (`ROLE_USER`, `ROLE_ADMIN`, `ROLE_SUPER_ADMIN`)
  * Custom login redirection (profile vs admin dashboard)
  * Voter restrictions (e.g. prevent superadmin deletion or role escalation)

* **User Management**
  * CRUD for users
  * Role assignment with rules
  * Password hashing (UserRepository)

* **Tests**
  * Functional tests
    * Login, logout, access restrictions

  * Unit tests
    * User entity (password, roles)
    * LoginSuccessHandler redirection

  * Integration tests
    * UserRepository password handling

---

## 🛠️ Tech Stack

* [Symfony 7](https://symfony.com/)
* [Doctrine ORM](https://www.doctrine-project.org/)
* [Twig](https://twig.symfony.com/)
* [API Platform](https://api-platform.com/)
* [VichUploader](https://github.com/dustin10/VichUploaderBundle)
* [PHPUnit](https://phpunit.de/)

---

## ⚙️ Installation

### Requirements

* PHP ≥ 8.3
* Composer
* Symfony CLI (or Docker, see below)
* Database (PostgreSQL/MySQL/SQLite)

### Quick steps

```bash
git clone https://github.com/cnierengarten-web/inklog.git
cd inklog
composer install

# Create database + run migrations
bin/console doctrine:database:create --if-not-exists
bin/console doctrine:migrations:migrate -n
bin/console doctrine:fixtures:load -n

# Start local server
symfony serve -d
```

👉 App available at [http://localhost:8000](http://localhost:8000)

### User credentials (fixtures)

**SuperAdmin**
* **login** : `superadmin@test.fr`
* **password** : `password`

**Admin:** 
* **login** : `admin@test.fr`
* **password** : `password`

**User**
* **login** : `alice@test.fr`
* **password** : `password`
---

## 🐳 Option: Docker

```bash
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate -n
docker compose exec php bin/console doctrine:fixtures:load -n
```

---

## ✅ Tests

```bash
# Run PHPUnit tests
composer test
```

---

## 📂 Project Structure

```
inklog/
├── apps/
│   └── inklog/           # Symfony sources (src/, config/, templates…)
├── docker/               # Docker configs (php, nginx, etc.)
├── docker-compose.yml    # Services stack
├── README.md             # Project documentation
├── .gitignore
├── .gitattributes
├── LICENSE
```

---

## 📸 Screenshots

TODO – insert 2–3 screenshots or a GIF:

* Articles list
* Create form
* API docs (Swagger UI)

---

## 📌 Roadmap

* [ ] Cover Image Upload with VichUploader for Articles
* [ ] REST API 
  * [ ] Articles & tags exposed with API Platform
  * [ ] Pagination, sort by date
  * [ ] Basic filters (title, tag)
  * [ ] Articles creations with security management
  * [ ] API tests (endpoint `/api/articles`)
* [ ] Article creation by author
  * [ ] Refactoring Articles
  * [ ] Security : author (with user role) can only edit his own articles
* [ ] Improve Tests coverage
* [ ] Full-text search
* [ ] Public demo deployment (optional)

---

## 📄 License

Open source under MIT License.
