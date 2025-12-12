<div id="top">

<!-- HEADER STYLE: CLASSIC -->
<div align="center">


# TESTMAKER

<em>Transform Learning with Effortless, Accurate Testing</em>

<!-- BADGES -->
<img src="https://img.shields.io/github/last-commit/SiddSparrow/TestMaker?style=flat&logo=git&logoColor=white&color=0080ff" alt="last-commit">
<img src="https://img.shields.io/github/languages/top/SiddSparrow/TestMaker?style=flat&color=0080ff" alt="repo-top-language">
<img src="https://img.shields.io/github/languages/count/SiddSparrow/TestMaker?style=flat&color=0080ff" alt="repo-language-count">

<em>Built with the tools and technologies:</em>

<img src="https://img.shields.io/badge/JSON-000000.svg?style=flat&logo=JSON&logoColor=white" alt="JSON">
<img src="https://img.shields.io/badge/Markdown-000000.svg?style=flat&logo=Markdown&logoColor=white" alt="Markdown">
<img src="https://img.shields.io/badge/npm-CB3837.svg?style=flat&logo=npm&logoColor=white" alt="npm">
<img src="https://img.shields.io/badge/Autoprefixer-DD3735.svg?style=flat&logo=Autoprefixer&logoColor=white" alt="Autoprefixer">
<img src="https://img.shields.io/badge/Redis-FF4438.svg?style=flat&logo=Redis&logoColor=white" alt="Redis">
<img src="https://img.shields.io/badge/PostCSS-DD3A0A.svg?style=flat&logo=PostCSS&logoColor=white" alt="PostCSS">
<img src="https://img.shields.io/badge/Composer-885630.svg?style=flat&logo=Composer&logoColor=white" alt="Composer">
<img src="https://img.shields.io/badge/JavaScript-F7DF1E.svg?style=flat&logo=JavaScript&logoColor=black" alt="JavaScript">
<br>
<img src="https://img.shields.io/badge/Vue.js-4FC08D.svg?style=flat&logo=vuedotjs&logoColor=white" alt="Vue.js">
<img src="https://img.shields.io/badge/PostgreSQL-316192?logo=postgresql&logoColor=white" alt="PostgreSql">
<img src="https://img.shields.io/badge/Docker-2496ED.svg?style=flat&logo=Docker&logoColor=white" alt="Docker">
<img src="https://img.shields.io/badge/XML-005FAD.svg?style=flat&logo=XML&logoColor=white" alt="XML">
<img src="https://img.shields.io/badge/PHP-777BB4.svg?style=flat&logo=PHP&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Vite-646CFF.svg?style=flat&logo=Vite&logoColor=white" alt="Vite">
<img src="https://img.shields.io/badge/Axios-5A29E4.svg?style=flat&logo=Axios&logoColor=white" alt="Axios">

</div>
<br>

---

## Table of Contents

- [Overview](#overview)
- [Getting Started](#getting-started)
    - [Prerequisites](#prerequisites)
    - [Installation](#installation)
    - [Usage](#usage)
    - [Testing](#testing)

---

## Overview

TestMaker is an all-in-one developer tool designed to simplify the development of assessment platforms. It provides a robust, containerized environment and a modular architecture that integrates Laravel, Vue 3, Inertia.js, and Tailwind CSS for building scalable, maintainable applications. The core features include:

- **Docker Environment:** Preconfigured PHP, database, cache, and queue services for consistent local development.
- **Modular Architecture:** Seamless integration of backend and frontend components supporting rapid feature development.
- **Content Management:** Rich models and controllers for questions, exams, subjects, and topics, enabling dynamic content workflows.
- **Testing & Seeding:** Built-in PHPUnit setup with factories for efficient testing and realistic data generation.
- **UI Components:** Reusable Vue components for forms, navigation, and modals, ensuring a cohesive user experience.
- **Developer Tools:** Commands for cache management and environment setup to streamline workflows.

---

## Key Features

- **Question Bank**: Create, organize, and tag questions by subject/topic
- **Exam Builder**: Drag-and-drop interface for assembling exams
- **Multiple Question Types**: Multiple choice, true/false, essays
- **User Management**: Role-based access control (teachers/admins)
- **Real-time Preview**: Instant exam preview with automatic scoring
- **Export Options**: Generate PDF exams and answer keys


## Getting Started

### Prerequisites

This project requires the following dependencies:

- **Programming Language:** PHP
- **Package Manager:** Composer, Npm
- **Container Runtime:** Docker

### Installation

Build TestMaker from the source and install dependencies:

1. **Clone the repository:**

    ```sh
    ❯ git clone https://github.com/SiddSparrow/TestMaker
    ```

2. **Navigate to the project directory:**

    ```sh
    ❯ cd TestMaker
    ```

3. **Install the dependencies:**

**Using [docker](https://www.docker.com/):**

```sh
❯ docker-compose up -d --build
  docker-compose exec php composer install
  docker-compose exec php npm install
```
**Using [composer](https://www.php.net/):**

```sh
❯ composer install
```
**Using [npm](https://www.npmjs.com/):**

```sh
❯ npm install
```

### Configuration

1. Copy environment file:
```bash
cp .env.example .env
```
2. Generate application key:
```bash
docker-compose exec php artisan key:generate
```
3. Run migrations:
```bash
docker-compose exec php php artisan migrate
```
4. Run seed: (optional for mock data)
```bash
docker-compose exec app php artisan db:seed
```
### Usage

Run the project with:

**Using [docker](https://www.docker.com/):**

```sh
docker run -it {image_name}
```
**Using [composer](https://www.php.net/):**

```sh
php {entrypoint}
```
**Using [npm](https://www.npmjs.com/):**

```sh
npm start
```

### Testing

Testmaker uses the {__test_framework__} test framework. Run the test suite with:

**Using [docker](https://www.docker.com/):**

```sh
echo 'INSERT-TEST-COMMAND-HERE'
```
**Using [composer](https://www.php.net/):**

```sh
vendor/bin/phpunit
```
**Using [npm](https://www.npmjs.com/):**

```sh
npm test
```

**For the frontend (vue.js):**

```sh
docker-compose exec php npm run dev
```


##  Support

For support, email fabio.duqueestrada@gmail.com or open an issue in the GitHub repository.

---
