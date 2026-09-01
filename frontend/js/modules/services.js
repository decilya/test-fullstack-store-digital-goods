/**
 * Модуль иконок сервисов.
 * 
 * ЛОГИКА:
 * - Рендерит список сервисов с иконками
 * - Hover эффект через CSS (transform: translateY)
 */

export function initServices() {
    const container = document.getElementById('servicesScroll');
    if (!container) return;
    
    const services = [
        { id: 'steam', name: 'Steam', icon: '🎮', color: '#1b2838' },
        { id: 'telegram', name: 'Telegram', icon: '✈️', color: '#2AABEE' },
        { id: 'roblox', name: 'Roblox', icon: '🎲', color: '#E2231A' },
        { id: 'brawl', name: 'Brawl Stars', icon: '⭐', color: '#FF6B35' },
        { id: 'pubg', name: 'PUBG Mobile', icon: '🔫', color: '#F2A900' },
        { id: 'appstore', name: 'App Store', icon: '🍎', color: '#007AFF' },
        { id: 'chatgpt', name: 'ChatGPT', icon: '🤖', color: '#10A37F' },
        { id: 'playstation', name: 'PlayStation', icon: '🎮', color: '#003087' },
        { id: 'tiktok', name: 'TikTok', icon: '', color: '#000000' },
        { id: 'mobilelegends', name: 'Mobile Legends', icon: '⚔️', color: '#1a237e' }
    ];
    
    container.innerHTML = services.map(service => `
        <div class="service-item" data-service="${escapeHtml(service.id)}">
            <div class="service-icon" style="background: ${service.color}">${service.icon}</div>
            <span class="service-name">${escapeHtml(service.name)}</span>
        </div>
    `).join('') + `
        <div class="service-item">
            <div class="service-icon" style="background: #F4F5F7">⋯</div>
            <span class="service-name">ещё 841</span>
        </div>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}