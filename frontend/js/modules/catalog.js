/**
 * Модуль выпадающего меню каталога.
 * 
 * ЛОГИКА:
 * - Клик по кнопке "Каталог" переключает видимость меню
 * - Клик вне меню закрывает его
 * - Escape закрывает меню
 */

export function initCatalog() {
    const catalogBtn = document.getElementById('catalogBtn');
    const catalogDropdown = document.getElementById('catalogDropdown');
    
    if (!catalogBtn || !catalogDropdown) return;
    
    let isOpen = false;
    
    function openMenu() {
        catalogDropdown.classList.add('active');
        catalogBtn.setAttribute('aria-expanded', 'true');
        isOpen = true;
    }
    
    function closeMenu() {
        catalogDropdown.classList.remove('active');
        catalogBtn.setAttribute('aria-expanded', 'false');
        isOpen = false;
    }
    
    function toggleMenu() {
        if (isOpen) closeMenu();
        else openMenu();
    }
    
    catalogBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMenu();
    });
    
    document.addEventListener('click', (e) => {
        if (isOpen && !catalogDropdown.contains(e.target) && e.target !== catalogBtn) {
            closeMenu();
        }
    });
    
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen) {
            closeMenu();
        }
    });
}