# 🎮 GamerStore — Магазин цифровых товаров для геймеров

Тестовое задание на позицию Fullstack-разработчика.

##  Описание

Площадка для продажи цифровых товаров: ключи игр, пополнения баланса, подписки, подарочные карты (аналог GGSel и подобных).

**Ключевая особенность проекта** — надежная однократная выдача ключей под нагрузкой и корректное восстановление после сбоев. Это не типовой магазин из туториала — архитектура спроектирована с учетом состязательных сценариев (гонки, дубли вебхуков, таймауты поставщиков). Особое внимание я уделил бэку - SOLID, абстракции, интерфейсы. Задание по верстке же полностью соответствует ТЗ. 

---

## 📸 Скриншоты интерфейса

### Главная страница — витрина магазина

![Главная страница](https://raw.githubusercontent.com/decilya/test-fullstack-store-digital-goods/main/1.png)

### блок товаров

![Карусель и товары](https://raw.githubusercontent.com/decilya/test-fullstack-store-digital-goods/main/2.png)

---

## 🚀 Быстрый старт

### Требования
- Docker 20.10+
- Docker Compose 2.0+
- WSL2 (для Windows)

### Установка и запуск

```bash
# 1. Перейти в папку проекта
cd ~/projects/gamer-store

# 2. Запустить контейнеры
docker-compose up -d --build

# 3. Инициализировать БД
docker-compose exec php sh -c "
    cd /var/www/html/backend &&
    mkdir -p database &&
    sqlite3 database/store.db < database/schema.sql &&
    sqlite3 database/store.db < database/seed.sql &&
    echo '✅ База данных инициализирована'
"

# 4. Открыть в браузере
# Frontend:  http://localhost:8081
# Админка:   http://localhost:8081/pages/admin-panel.php
# Статус заказа: http://localhost:8081/pages/order-status.php?id=<order_id>
# Health:    http://localhost:8081/api/health.php
