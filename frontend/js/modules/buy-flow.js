/**
 * Модуль полного флоу покупки.
 * 
 * ЛОГИКА:
 * 1. Клик "Купить" → открывается модалка с деталями
 * 2. Опционально ввод промокода (расчет скидки на сервере)
 * 3. Клик "Оплатить (успех)" → POST /api/orders.php (создание заказа)
 * 4. Сразу POST /api/pay.php (эмуляция платежа, шлет webhook)
 * 5. Редирект на страницу статуса заказа
 * 
 * ЗАЩИТА ОТ ДВОЙНОГО КЛИКА:
 * - Флаг isProcessing не дает отправить повторно
 * - Кнопки блокируются на время запроса
 */

let isProcessing = false;
let currentProduct = null;
let allProducts = []; // Храним все товары для фильтрации

/**
 * Инициализация списка товаров (загрузка с бэкенда)
 */
export async function initProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    // Показываем заглушки пока грузятся
    grid.innerHTML = Array(5).fill('<div class="product-card"><div class="product-image"></div><div class="product-info">Загрузка...</div></div>').join('');
    
    try {
        const response = await fetch('/api/products.php');
        const result = await response.json();
        
        if (!result.success) throw new Error(result.error || 'Ошибка загрузки');
        
        allProducts = result.data; // Сохраняем все товары
        renderProducts(allProducts.slice(0, 5)); // Показываем первые 5
        
        console.log(`✅ Загружено ${allProducts.length} товаров`);
    } catch (e) {
        console.error('Ошибка загрузки товаров:', e);
        grid.innerHTML = '<p style="color: #EF4444;">Не удалось загрузить товары</p>';
    }
}

/**
 * Рендер товаров в сетку
 * @param {Array} products Массив товаров для отображения
 */
function renderProducts(products) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    if (products.length === 0) {
        grid.innerHTML = '<p style="text-align: center; color: #6B7280; padding: 40px;">Товары не найдены</p>';
        return;
    }
    
    grid.innerHTML = products.map(p => `
        <div class="product-card" data-sku="${escapeHtml(p.sku)}" data-name="${escapeHtml(p.name)}" data-price="${p.price}">
            <div class="product-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
            <div class="product-info">
                <div class="product-title">${escapeHtml(p.name)}</div>
                <div class="product-price">
                    <span class="price-current">${p.price} ₽</span>
                </div>
                <button class="buy-btn">Купить</button>
            </div>
        </div>
    `).join('');
}

/**
 * Инициализация флоу покупки: обработчики на "Купить"
 */
export function initBuyFlow() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    // Делегирование событий
    grid.addEventListener('click', async (e) => {
        if (!e.target.classList.contains('buy-btn')) return;
        
        const card = e.target.closest('.product-card');
        if (!card) return;
        
        currentProduct = {
            sku: card.dataset.sku,
            name: card.dataset.name,
            price: parseFloat(card.dataset.price),
        };
        
        openPaymentModal(currentProduct);
    });
    
    // Инициализация табов
    initTabs();
    
    setupModalHandlers();
}

/**
 * Инициализация табов с фильтрацией по категориям
 */
function initTabs() {
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Убираем активный класс со всех табов
            tabs.forEach(t => t.classList.remove('tab--active'));
            // Добавляем активный класс на кликнутый таб
            tab.classList.add('tab--active');
            
            // Получаем категорию из data-атрибута
            const category = tab.dataset.category;
            
            // Фильтруем товары
            if (category === 'all') {
                renderProducts(allProducts.slice(0, 5));
            } else {
                const filtered = allProducts.filter(p => p.type === category);
                renderProducts(filtered);
            }
        });
    });
}

/**
 * Открыть модалку оплаты
 */
function openPaymentModal(product) {
    const modal = document.getElementById('paymentModal');
    document.getElementById('modalOrderId').textContent = '—';
    document.getElementById('modalProductName').textContent = product.name;
    document.getElementById('modalAmount').textContent = `${product.price} ₽`;
    document.getElementById('discountBlock').style.display = 'none';
    document.getElementById('promoInput').value = '';
    document.getElementById('modalStatus').className = 'modal__status';
    document.getElementById('modalStatus').textContent = '';
    
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
}

/**
 * Закрыть модалку оплаты
 */
function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
}

/**
 * Настройка обработчиков модалки
 */
function setupModalHandlers() {
    document.getElementById('modalClose').addEventListener('click', closePaymentModal);
    document.querySelector('.modal__overlay').addEventListener('click', closePaymentModal);
    
    document.getElementById('applyPromoBtn').addEventListener('click', applyPromoCode);
    document.getElementById('paySuccessBtn').addEventListener('click', () => processPayment(true));
    document.getElementById('payFailBtn').addEventListener('click', () => processPayment(false));
}

/**
 * Применить промокод: запрос к бэкенду для расчета скидки
 * 
 * ВАЖНО: скидку считает СЕРВЕР, клиенту не доверяем!
 */
async function applyPromoCode() {
    const code = document.getElementById('promoInput').value.trim();
    if (!code) return;
    
    const statusEl = document.getElementById('modalStatus');
    statusEl.className = 'modal__status active';
    statusEl.textContent = 'Проверяем промокод...';
    
    try {
        const response = await fetch('/api/orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sku: currentProduct.sku,
                amount: currentProduct.price,
                promo_code: code,
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            document.getElementById('modalOrderId').textContent = data.order_id;
            document.getElementById('discountBlock').style.display = 'block';
            document.getElementById('discountAmount').textContent = `-${data.discount} ₽ (${code})`;
            document.getElementById('finalAmount').textContent = `${data.final_amount} ₽`;
            statusEl.className = 'modal__status active success';
            statusEl.textContent = '✓ Промокод применен. Теперь можно оплатить.';
        } else {
            statusEl.className = 'modal__status active error';
            statusEl.textContent = '✗ ' + (result.error || 'Промокод не сработал');
        }
    } catch (e) {
        statusEl.className = 'modal__status active error';
        statusEl.textContent = '✗ Ошибка сети';
    }
}

/**
 * Обработка оплаты: создание заказа + симуляция платежа
 * 
 * ЗАЩИТА ОТ ДВОЙНОГО КЛИКА:
 * - Флаг isProcessing блокирует повторные вызовы
 * - Кнопки disabled на время запроса
 */
async function processPayment(success) {
    if (isProcessing) return;
    isProcessing = true;
    
    const statusEl = document.getElementById('modalStatus');
    const successBtn = document.getElementById('paySuccessBtn');
    const failBtn = document.getElementById('payFailBtn');
    successBtn.disabled = true;
    failBtn.disabled = true;
    
    statusEl.className = 'modal__status active';
    statusEl.textContent = 'Создаем заказ...';
    
    let orderId = document.getElementById('modalOrderId').textContent;
    
    try {
        // Шаг 1: создание заказа (если еще не создан)
        if (!orderId || orderId === '—') {
            const orderResponse = await fetch('/api/orders.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    sku: currentProduct.sku,
                    amount: currentProduct.price,
                    promo_code: document.getElementById('promoInput').value.trim() || null,
                })
            });
            
            const orderResult = await orderResponse.json();
            if (!orderResult.success) {
                throw new Error(orderResult.error || 'Ошибка создания заказа');
            }
            
            orderId = orderResult.data.order_id;
            document.getElementById('modalOrderId').textContent = orderId;
        }
        
        statusEl.textContent = 'Эмулируем платеж...';
        
        // Шаг 2: эмуляция платежа
        const payResponse = await fetch('/api/pay.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                order_id: orderId,
                success: success,
                amount: currentProduct.price,
            })
        });
        
        const payResult = await payResponse.json();
        
        if (success) {
            statusEl.className = 'modal__status active success';
            statusEl.innerHTML = `✓ Оплата прошла! Переходим к статусу заказа...`;
            
            setTimeout(() => {
                window.location.href = `/pages/order-status.php?id=${orderId}`;
            }, 1500);
        } else {
            statusEl.className = 'modal__status active error';
            statusEl.textContent = '✗ Оплата не прошла';
            successBtn.disabled = false;
            failBtn.disabled = false;
        }
        
    } catch (e) {
        statusEl.className = 'modal__status active error';
        statusEl.textContent = '✗ ' + e.message;
        successBtn.disabled = false;
        failBtn.disabled = false;
    } finally {
        isProcessing = false;
    }
}

/**
 * Экранирование HTML для защиты от XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
