/**
 * Модуль переключателя валют.
 * 
 * ПО ТЗ: "Пересчет суммы делать не нужно"
 * 
 * ЛОГИКА:
 * - Меняет только визуальное состояние (активный класс)
 * - НЕ пересчитывает цену (это заглушка в макете)
 */

export function initCurrency() {
    const buttons = document.querySelectorAll('.currency-btn');
    
    if (!buttons.length) return;
    
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            buttons.forEach(b => b.classList.remove('currency-btn--active'));
            btn.classList.add('currency-btn--active');
            
            console.log(`Валюта переключена: ${btn.dataset.currency}`);
        });
    });
}