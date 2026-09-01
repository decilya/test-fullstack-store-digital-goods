-- Схема БД для GamerStore
-- Все таблицы используют подготовленные выражения для защиты от SQL injection

PRAGMA foreign_keys = ON;

-- Товары
CREATE TABLE IF NOT EXISTS products (
    sku VARCHAR(50) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'RUB',
    image VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Пул ключей (внутренний, для fallback когда поставщики упали)
CREATE TABLE IF NOT EXISTS keys_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sku VARCHAR(50) NOT NULL,
    code VARCHAR(100) NOT NULL UNIQUE,
    status VARCHAR(20) DEFAULT 'available' CHECK(status IN ('available', 'reserved', 'used')),
    order_id VARCHAR(50),
    reserved_at DATETIME,
    used_at DATETIME,
    FOREIGN KEY (sku) REFERENCES products(sku)
);
CREATE INDEX IF NOT EXISTS idx_keys_status_sku ON keys_pool(status, sku);

-- Заказы
CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(50) PRIMARY KEY,
    sku VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(30) DEFAULT 'created' CHECK(status IN (
        'created', 'delivering', 'delivered', 
        'payment_failed', 'out_of_stock', 'delivery_failed'
    )),
    promo_code VARCHAR(50),
    discount_amount DECIMAL(10,2) DEFAULT 0,
    issued_key TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME,
    delivered_at DATETIME,
    FOREIGN KEY (sku) REFERENCES products(sku)
);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);

-- Webhook события (для идемпотентности)
CREATE TABLE IF NOT EXISTS webhook_events (
    event_id VARCHAR(100) PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL,
    order_existed_at_receive INTEGER DEFAULT 1,
    applied_at DATETIME,
    processed_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_webhooks_order ON webhook_events(order_id);

-- Промокоды
CREATE TABLE IF NOT EXISTS promo_codes (
    code VARCHAR(50) PRIMARY KEY,
    type VARCHAR(20) NOT NULL CHECK(type IN ('percent', 'amount')),
    value DECIMAL(10,2) NOT NULL,
    max_uses INTEGER NOT NULL,
    current_uses INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    CHECK(current_uses <= max_uses)
);

-- Использования промокодов (уникальность на пару промокод+заказ)
CREATE TABLE IF NOT EXISTS promo_code_uses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    promo_code VARCHAR(50) NOT NULL,
    order_id VARCHAR(50) NOT NULL,
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(promo_code, order_id),
    FOREIGN KEY (promo_code) REFERENCES promo_codes(code),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Кэш поставщиков (защита от ловушки таймаута)
CREATE TABLE IF NOT EXISTS supplier_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    supplier VARCHAR(50) NOT NULL,
    request_id VARCHAR(100) NOT NULL,
    code VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(supplier, request_id)
);