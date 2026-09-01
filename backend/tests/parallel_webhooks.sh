#!/bin/bash

# Тест реальных параллельных HTTP запросов через curl.
# Запускает 50 параллельных curl-запросов на webhook endpoint.
# 
# Использование:
#   chmod +x backend/tests/parallel_webhooks.sh
#   ./backend/tests/parallel_webhooks.sh http://localhost:8080/api/webhook.php ORDER-001 EVT-001

URL=${1:-"http://localhost:8080/api/webhook.php"}
ORDER_ID=${2:-"ord_test_race"}
EVENT_ID=${3:-"evt_race_$(date +%s)"}
COUNT=${4:-50}

echo " Parallel webhooks test"
echo "   URL: $URL"
echo "   Order: $ORDER_ID"
echo "   Event ID: $EVENT_ID"
echo "   Count: $COUNT"
echo ""

PAYLOAD="{\"event_id\":\"$EVENT_ID\",\"order_id\":\"$ORDER_ID\",\"status\":\"paid\",\"amount\":100,\"currency\":\"RUB\",\"created_at\":\"$(date -u +%Y-%m-%dT%H:%M:%SZ)\"}"

echo "Payload: $PAYLOAD"
echo ""
echo "Запускаю $COUNT параллельных запросов..."
echo ""

TMPDIR=$(mktemp -d)

for i in $(seq 1 $COUNT); do
    (
        HTTP_CODE=$(curl -s -o "$TMPDIR/response_$i.txt" -w "%{http_code}" \
            -X POST "$URL" \
            -H "Content-Type: application/json" \
            -d "$PAYLOAD")
        echo "$HTTP_CODE" > "$TMPDIR/code_$i.txt"
    ) &
done

wait

echo ""
echo "📊 Результаты:"

SUCCESS=0
ERROR=0
for i in $(seq 1 $COUNT); do
    CODE=$(cat "$TMPDIR/code_$i.txt" 2>/dev/null || echo "000")
    if [ "$CODE" = "200" ]; then
        SUCCESS=$((SUCCESS + 1))
    else
        ERROR=$((ERROR + 1))
    fi
done

echo "  HTTP 200: $SUCCESS"
echo "  Другие: $ERROR"
echo ""

echo "📝 Первый ответ:"
cat "$TMPDIR/response_1.txt" 2>/dev/null || echo "(нет ответа)"
echo ""

echo "🔍 Проверяем заказ через GET /api/orders.php?id=$ORDER_ID"
ORDER_INFO=$(curl -s "$URL/../orders.php?id=$ORDER_ID" 2>/dev/null || echo "")
echo "$ORDER_INFO"

rm -rf "$TMPDIR"

echo ""
echo "✅ Тест завершен"