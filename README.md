# BirdDex

> Track, discover, and collect real-world bird sightings in a Pokédex-inspired digital encyclopedia.

BirdDex is an open-source, zero-cost bird-sighting tracker designed for nature enthusiasts and birdwatchers. Inspired by creature collection games, BirdDex turns real-world birding into a gamified discovery experience.

---

## 📌 Project Overview

BirdDex provides birdwatchers with a structured digital logbook to record sightings, learn about different species, and build a personal collection.

- **Gamified Collection:** Track avian encounters with stats, timestamps, and location entries in a Pokédex-style interface.
- **AI-Powered Identification (In Development):** Planned computer vision integration to identify species directly from uploaded photos.
- **Zero-Cost Architecture:** Built strictly using free, open-source technologies to eliminate hosting and API overhead.

---

## 🛠️ Technology Stack

| Layer                    | Technology                             | Description                                                                       |
| :----------------------- | :------------------------------------- | :-------------------------------------------------------------------------------- |
| **Frontend**             | [Next.js](https://nextjs.org/) (React) | Modern App Router UI, server-side rendering, Tailwind CSS                         |
| **Backend**              | [Laravel](https://laravel.com/) (PHP)  | RESTful API, authentication, database ORM                                         |
| **Database**             | SQLite / MySQL                         | Relational data store for users, species, and sighting logs                       |
| **AI / Computer Vision** | _Planned_                              | Lightweight bird classification model (e.g., TensorFlow.js / free inference tier) |

---

## 📁 Repository Structure

BirdDex is managed as a unified monorepo:

```text
birddex/
├── .github/
│   └── PULL_REQUEST_TEMPLATE.md   # Standardization for pull requests
├── api/                            # Laravel RESTful API backend
│   ├── app/                        # Application logic, models, controllers
│   ├── config/                     # Framework configuration
│   └── routes/                     # API endpoint definitions
├── web/                            # Next.js web application frontend
│   ├── src/
│   │   ├── app/                    # Next.js App Router pages and layouts
│   │   │   └── favicon.ico         # App icon
│   │   └── components/             # Reusable UI elements
│   └── package.json
└── README.md                       # Project documentation
```

---

## 🚀 Getting Started

### Prerequisites

Ensure you have the following installed locally:

- **PHP** `>= 8.2` and **Composer**
- **Node.js** `>= 18.x` and **npm** or **pnpm**
- **Git**

---

### Installation & Setup

#### 1. Clone the Repository

```bash
git clone https://github.com/your-username/birddex.git
cd birddex
```

#### 2. Configure the Backend (Laravel API)

```bash
# Navigate to API directory
cd api

# Install PHP dependencies
composer install

# Copy environment template
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations & seeders
php artisan migrate --seed
```

#### 3. Configure the Frontend (Next.js)

```bash
# Navigate to Web directory (from root)
cd ../web

# Install Node dependencies
npm install

# Copy environment template
cp .env.example .env.local
```

---

## 🏃 Running Locally

Run both the API and Web services concurrently during development.

### Start Backend API

```bash
cd api
php artisan serve
```

- **API URL:** `http://127.0.0.1:8000`
- **Health Check:** `http://127.0.0.1:8000/api`

### Start Frontend Web App

```bash
cd web
npm run dev
```

- **Web App URL:** `http://localhost:3000`

---

## 🔑 Environment Variables

### Backend (`api/.env`)

```ini
APP_NAME=BirdDex
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

FRONTEND_URL=http://localhost:3000

DB_CONNECTION=sqlite
# DB_DATABASE=/path/to/database.sqlite
```

### Frontend (`web/.env.local`)

```ini
NEXT_PUBLIC_API_BASE_URL=http://127.0.0.1:8000/api
```

---

## 🗺️ Product Roadmap

- [x] **Monorepo Setup:** Unified Laravel + Next.js repository architecture.
- [x] **PR & Code Quality Guidelines:** Standardized contribution templates.
- [ ] **Milestone 1: Core Species Catalog:** Database of bird species with details, habitat, and media.
- [ ] **Milestone 2: Sighting Logbook:** Ability for authenticated users to log sightings with photo uploads, dates, and locations.
- [ ] **Milestone 3: Pokédex Discovery UI:** Gamified collection interface showing seen vs. caught species.
- [ ] **Milestone 4: Zero-Cost AI Bird ID:** Integration of an open-source image classification model for automated identification.

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Review the PR guidelines in [`.github/PULL_REQUEST_TEMPLATE.md`](./.github/PULL_REQUEST_TEMPLATE.md).
2. Create a feature branch (`git checkout -b feat/bird-catalog`).
3. Commit changes using standard conventional commits (`git commit -m "feat: add species search endpoint"`).
4. Push to your branch and submit a Pull Request.

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).
