/**
 * Главный файл приложения.
 * 
 * Инициализирует все модули после загрузки DOM.
 * Используется ES6 modules (type="module" в HTML).
 */

import { initCarousel } from './modules/carousel.js';
import { initCatalog } from './modules/catalog.js';
import { initCurrency } from './modules/currency.js';
import { initProducts, initBuyFlow } from './modules/buy-flow.js';
import { initServices } from './modules/services.js';

// Ждем загрузки DOM перед инициализацией
document.addEventListener('DOMContentLoaded', async () => {
    console.log('🚀 GamerStore: инициализация');
    
    // Инициализация интерактивных элементов
    initCarousel();
    initCatalog();
    initCurrency();
    initServices();
    
    // Загрузка товаров и инициализация флоу покупки
    await initProducts();
    initBuyFlow();
    
    console.log('✅ GamerStore: готов к работе');
});