# Inklog 🖋️

[![CI](https://github.com/cnierengarten-web/inklog/actions/workflows/ci.yml/badge.svg)](https://github.com/cnierengarten-web/inklog/actions/workflows/ci.yml)

Blog prototype built with **Symfony 7**.
Goal: explore a modern, testable architecture (CRUD, upload, API) within a simple but complete project.

**Open to freelance Symfony missions (remote / Nantes) !**

---

## 🚀 Features

* **Articles, categories & Tags**

    * Create / edit / delete articles
    * Create / edit / delete categories and tags
    * Associate tags and categories
    * Automatic slugs
    * Upload image on articles with VichUploader
    * Simple API to get Articles
    * Display last published articles by tag, category, author

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
    * API

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

### Admin
* Articles list
![Articles list screenshot](/docs/admin-articles-list.png)


* Article edition
![Article edition screenshot](/docs/admin-article-edition.png)


* Users list : user cannot delete himself nor superadmin user
![Users list screenshot](/docs/admin-users-list.png)


### Front
* Front - Articles list
![Front Articles list screenshot](/docs/front-articles-list.png)


* Front - Article page
![Front Article view screenshot](/docs/front-articles-view.png)

### API
* API docs (Swagger UI)
![Api swagger](/docs/api-swagger.png)


* Request
![Api swagger result](/docs/api-swagger-result.png)

---

## 🔧 Possible Improvements

Inklog is intentionally limited to a simple scope (CRUD, security, upload, basic API).  
Some evolutions could be explored in other projects:

- Refactor article management with user roles (author/editor).
- Extend test coverage (edge cases, validation).
- Richer API (authentication, advanced filters, mutations).

👉 This allows Inklog to remain clear and easy to read, while keeping space for future dedicated projects.


---

## 📬 Contact

- LinkedIn : https://www.linkedin.com/in/claire-nierengarten-0bb92549/
- Email : claire.nierengarten.pro [at] gmail [dot] com
- GitHub : https://github.com/cnierengarten-web/inklog

---

## 📄 License

Open source under MIT License.