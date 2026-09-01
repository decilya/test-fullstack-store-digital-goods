# 🎮 GamerStore — Магазин цифровых товаров

Тестовое задание на позицию Fullstack-разработчик.

## 🚀 Быстрый старт

```bash
# 1. Запустить контейнеры
docker-compose up -d --build

# 2. Инициализировать БД
docker-compose exec php sh -c "
    cd /var/www/html/backend &&
    sqlite3 database/store.db < database/schema.sql &&
    sqlite3 database/store.db < database/seed.sql &&
    echo 'DB initialized'
"

# 3. Открыть в браузере
# Frontend: http://localhost:8080
# Админка: http://localhost:8080/admin-panel.php
# Статус заказа: http://localhost:8080/order-status.php?id=<order_id>