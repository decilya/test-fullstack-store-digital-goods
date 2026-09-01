<?php
declare(strict_types=1);

/**
 * Админка - список проблемных заказов с кнопкой повторной выдачи.
 * Защита от XSS через htmlspecialchars во всех выводах.
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка - Проблемные заказы</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { margin-bottom: 20px; }
        .card { background: white; border-radius: 12px; padding: 20px; margin-bottom: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .status { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-out_of_stock { background: #FED7AA; color: #9A3412; }
        .status-delivery_failed { background: #FEE2E2; color: #991B1B; }
        button { padding: 8px 16px; background: #000; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        button:hover { opacity: 0.9; }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .empty { text-align: center; padding: 40px; color: #9CA3AF; }
        .result { margin-top: 12px; padding: 12px; border-radius: 8px; display: none; }
        .result.success { background: #D1FAE5; color: #065F46; display: block; }
        .result.error { background: #FEE2E2; color: #991B1B; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Админка: Проблемные заказы</h1>
        <p>Список заказов со статусами <strong>out_of_stock</strong> и <strong>delivery_failed</strong>.</p>
        
        <div id="orders-list">
            <div class="empty">Загрузка...</div>
        </div>
        
        <p style="margin-top: 20px;">
            <a href="/" style="color: #000;">← В магазин</a>
        </p>
    </div>
    
    <script>
        // Защита от двойного клика
        let isProcessing = false;
        
        async function loadOrders() {
            try {
                const response = await fetch('/api/admin.php?action=problematic');
                const result = await response.json();
                const container = document.getElementById('orders-list');
                
                if (!result.success || !result.data.length) {
                    container.innerHTML = '<div class="empty">Проблемных заказов нет ✓</div>';
                    return;
                }
                
                container.innerHTML = result.data.map(order => `
                    <div class="card" data-order-id="${escapeHtml(order.id)}">
                        <div class="card-header">
                            <div>
                                <strong>#${escapeHtml(order.id)}</strong>
                                <div style="font-size: 13px; color: #6B7280;">
                                    ${escapeHtml(order.product_name || order.sku)} • ${escapeHtml(order.final_amount)} ₽
                                </div>
                            </div>
                            <span class="status status-${escapeHtml(order.status)}">${escapeHtml(order.status)}</span>
                        </div>
                        <div style="font-size: 13px; color: #6B7280; margin-bottom: 12px;">
                            Создан: ${escapeHtml(order.created_at)}
                            ${order.paid_at ? ' • Оплачен: ' + escapeHtml(order.paid_at) : ''}
                        </div>
                        <button onclick="retryDelivery('${escapeHtml(order.id)}', this)" data-order="${escapeHtml(order.id)}">
                            Повторить выдачу
                        </button>
                        <div class="result" id="result-${escapeHtml(order.id)}"></div>
                    </div>
                `).join('');
            } catch (e) {
                console.error('Ошибка загрузки:', e);
            }
        }
        
        async function retryDelivery(orderId, button) {
            // Защита от двойного клика
            if (isProcessing) return;
            isProcessing = true;
            
            button.disabled = true;
            button.textContent = 'Выполняется...';
            
            const resultDiv = document.getElementById('result-' + orderId);
            
            try {
                const response = await fetch('/api/admin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({order_id: orderId})
                });
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `✓ ${escapeHtml(result.message || 'Выдано')}${result.code ? ': ' + escapeHtml(result.code) : ''}${result.idempotent ? ' (идемпотентно)' : ''}`;
                    if (result.code) {
                        setTimeout(() => loadOrders(), 1500);
                    }
                } else {
                    resultDiv.className = 'result error';
                    resultDiv.textContent = '✗ ' + escapeHtml(result.error || 'Ошибка');
                    button.disabled = false;
                    button.textContent = 'Повторить выдачу';
                }
            } catch (e) {
                resultDiv.className = 'result error';
                resultDiv.textContent = ' Сетевая ошибка';
                button.disabled = false;
                button.textContent = 'Повторить выдачу';
            } finally {
                isProcessing = false;
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        loadOrders();
        setInterval(loadOrders, 10000);
    </script>
</body>
</html>