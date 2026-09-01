<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../repositories/OrderRepository.php';

use App\Repositories\OrderRepository;

// Защита от XSS в GET параметре
$orderId = isset($_GET['id']) ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') : null;
$order = null;

if ($orderId) {
    $db = Database::getInstance();
    $orderRepo = new OrderRepository($db);
    $order = $orderRepo->findById($orderId);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статус заказа <?= $orderId ?? '' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .status-page { max-width: 600px; margin: 40px auto; padding: 20px; }
        .status-card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .status-badge { display: inline-block; padding: 8px 16px; border-radius: 20px; font-weight: 600; margin: 16px 0; }
        .status-created { background: #F4F5F7; color: #6B7280; }
        .status-delivering { background: #FEF3C7; color: #92400E; }
        .status-delivered { background: #D1FAE5; color: #065F46; }
        .status-payment_failed { background: #FEE2E2; color: #991B1B; }
        .status-out_of_stock { background: #FED7AA; color: #9A3412; }
        .status-delivery_failed { background: #FED7AA; color: #9A3412; }
        .issued-key { font-family: monospace; font-size: 24px; padding: 20px; background: #F4F5F7; border-radius: 8px; margin: 16px 0; word-break: break-all; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F2F4F7; }
        .btn-back { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #000; color: white; text-decoration: none; border-radius: 10px; }
        .not-found { text-align: center; padding: 40px; color: #9CA3AF; }
    </style>
</head>
<body>
    <div class="status-page">
        <div class="status-card">
            <?php if (!$order): ?>
                <div class="not-found">
                    <h2>Заказ не найден</h2>
                    <p>Проверьте ID заказа</p>
                </div>
            <?php else: ?>
                <h1>Заказ #<?= htmlspecialchars($order['id']) ?></h1>
                
                <?php
                $statusLabels = [
                    'created' => 'Создан, ожидает оплаты',
                    'delivering' => 'Оплачен, идет выдача...',
                    'delivered' => 'Доставлен ✓',
                    'payment_failed' => 'Оплата не прошла',
                    'out_of_stock' => 'Оплачен, нет в наличии',
                    'delivery_failed' => 'Ошибка выдачи',
                ];
                ?>
                
                <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                    <?= $statusLabels[$order['status']] ?? htmlspecialchars($order['status']) ?>
                </span>
                
                <div class="info-row">
                    <span>SKU:</span>
                    <strong><?= htmlspecialchars($order['sku']) ?></strong>
                </div>
                <div class="info-row">
                    <span>Сумма:</span>
                    <strong><?= htmlspecialchars($order['amount']) ?> ₽</strong>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="info-row">
                    <span>Скидка (<?= htmlspecialchars($order['promo_code'] ?? '') ?>):</span>
                    <strong>-<?= htmlspecialchars($order['discount_amount']) ?> ₽</strong>
                </div>
                <div class="info-row">
                    <span>К оплате:</span>
                    <strong><?= htmlspecialchars($order['final_amount']) ?> ₽</strong>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span>Создан:</span>
                    <span><?= htmlspecialchars($order['created_at']) ?></span>
                </div>
                
                <?php if ($order['status'] === 'delivered' && $order['issued_key']): ?>
                    <h3 style="margin-top: 24px;">Ваш ключ:</h3>
                    <div class="issued-key"><?= htmlspecialchars($order['issued_key']) ?></div>
                    <button onclick="copyKey(this)" class="btn-back">Скопировать ключ</button>
                    <script>
                        function copyKey(btn) {
                            const key = <?= json_encode($order['issued_key']) ?>;
                            navigator.clipboard.writeText(key).then(() => {
                                btn.textContent = 'Скопировано!';
                            });
                        }
                    </script>
                <?php endif; ?>
                
                <?php if (in_array($order['status'], ['out_of_stock', 'delivery_failed'])): ?>
                    <div style="margin-top: 24px; padding: 16px; background: #FEF3C7; border-radius: 8px;">
                        <p>⚠️ Ожидайте пополнения или обратитесь в поддержку.</p>
                        <p>Администратор может выполнить повторную выдачу.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <a href="/" class="btn-back">← Вернуться в магазин</a>
            <a href="/admin-panel.php" class="btn-back" style="background: #6B7280; margin-left: 8px;">Админка</a>
        </div>
    </div>
</body>
</html>