# Проект
Laravel + Vue.js, запускается через Docker.

## Требования

- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- Git

## Подготовка к запуску 

Добавить в docker/mysql файл с бд init.sql

## Запуск

```bash
git clone https://github.com/you/project.git
cd project
cp .env.example .env
```

Откройте `.env` и заполните:

```env
DB_DATABASE=gis
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Затем:

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
```

Открыть в браузере: http://localhost:8000

## Полезные команды

```bash
docker compose up -d        # запустить
docker compose down         # остановить
docker compose ps           # статус контейнеров
docker compose logs -f      # логи
docker compose exec app bash  # войти в контейнер
```

## Если что-то сломалось

```bash
docker compose down
docker volume prune -f
docker compose up -d --build
docker compose exec app php artisan key:generate
```
