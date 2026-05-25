// main.js - клиентская логика для сайта железнодорожных перевозок

document.addEventListener('DOMContentLoaded', function() {
    
    // ==================== ПЛАВНАЯ ПРОКРУТКА ====================
    const smoothLinks = document.querySelectorAll('a[href^="#"]:not([href="#"])');
    
    smoothLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Обновляем URL без скачка
                history.pushState(null, null, targetId);
            }
        });
    });
    
    // ==================== АВТОКОМПЛЕКТ СТАНЦИЙ (расширенный) ====================
    const fromInput = document.getElementById('fromStation');
    const toInput = document.getElementById('toStation');
    
    function setupAutocomplete(inputElement) {
        if (!inputElement) return;
        
        let datalist = document.getElementById('stationsList');
        if (!datalist) return;
        
        const originalOptions = Array.from(datalist.options).map(opt => opt.value);
        
        inputElement.addEventListener('input', function(e) {
            const value = this.value.toLowerCase();
            // Очищаем и перезаполняем datalist
            while (datalist.options.length > 0) {
                datalist.remove(0);
            }
            
            const filtered = originalOptions.filter(opt => 
                opt.toLowerCase().includes(value)
            );
            
            filtered.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt;
                datalist.appendChild(option);
            });
            
            if (filtered.length === 0 && value.length > 0) {
                const option = document.createElement('option');
                option.value = 'Станция не найдена';
                datalist.appendChild(option);
            }
        });
    }
    
    setupAutocomplete(fromInput);
    setupAutocomplete(toInput);
    
    // ==================== ВАЛИДАЦИЯ ФОРМЫ ПОИСКА ====================
    const searchForm = document.querySelector('form[action=""]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const fromStation = document.getElementById('fromStation')?.value.trim();
            const toStation = document.getElementById('toStation')?.value.trim();
            const dateInput = document.querySelector('input[name="travel_date"]');
            
            if (!fromStation || !toStation) {
                e.preventDefault();
                showNotification('Пожалуйста, укажите станцию отправления и назначения', 'error');
                return;
            }
            
            if (fromStation === toStation) {
                e.preventDefault();
                showNotification('Станция отправления и назначения не могут совпадать', 'error');
                return;
            }
            
            if (dateInput && dateInput.value) {
                const selectedDate = new Date(dateInput.value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate < today) {
                    e.preventDefault();
                    showNotification('Дата поездки не может быть в прошлом', 'error');
                    return;
                }
            }
        });
    }
    
    // ==================== ФУНКЦИЯ УВЕДОМЛЕНИЙ ====================
    function showNotification(message, type = 'info') {
        // Удаляем старые уведомления
        const oldToast = document.querySelector('.custom-toast');
        if (oldToast) oldToast.remove();
        
        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '❌';
        if (type === 'warning') icon = '⚠️';
        
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${icon}</span>
                    <span class="toast-message">${message}</span>
                    <button class="toast-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Анимация появления
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Автоматическое скрытие через 4 секунды
        const timeout = setTimeout(() => {
            hideToast(toast);
        }, 4000);
        
        // Закрытие по кнопке
        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                clearTimeout(timeout);
                hideToast(toast);
            });
        }
        
        function hideToast(toastElement) {
            toastElement.classList.remove('show');
            setTimeout(() => toastElement.remove(), 300);
        }
    }
    
    // ==================== ЗАГРУЗКА СПИСКА ПОПУЛЯРНЫХ МАРШРУТОВ (AJAX) ====================
    async function loadPopularRoutes() {
        const container = document.querySelector('.popular-routes-container');
        if (!container) return;
        
        try {
            const response = await fetch('/api/popular-routes.php');
            if (!response.ok) throw new Error('Ошибка загрузки');
            
            const routes = await response.json();
            
            if (routes.length === 0) {
                container.innerHTML = '<p>Нет доступных маршрутов</p>';
                return;
            }
            
            container.innerHTML = routes.map(route => `
                <div class="popular-route-item">
                    <i class="fas fa-train"></i>
                    <div class="route-path">
                        <span>${escapeHtml(route.from_station)}</span>
                        <i class="fas fa-arrow-right"></i>
                        <span>${escapeHtml(route.to_station)}</span>
                    </div>
                    <div class="route-price">от ${Number(route.price).toLocaleString()} ₽</div>
                </div>
            `).join('');
            
        } catch (error) {
            console.error('Ошибка:', error);
            container.innerHTML = '<p>Не удалось загрузить популярные маршруты</p>';
        }
    }
    
    // ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    
    // ==================== АНИМАЦИЯ ПРИ ПРОКРУТКЕ ====================
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.advantage-card, .result-card, .schedule-table-wrapper');
        
        elements.forEach(el => {
            const rect = el.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            if (rect.top < windowHeight - 100) {
                el.classList.add('animated');
            }
        });
    };
    
    // Добавляем классы для анимации
    const style = document.createElement('style');
    style.textContent = `
        .advantage-card, .result-card, .schedule-table-wrapper {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .advantage-card.animated, .result-card.animated, .schedule-table-wrapper.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .custom-toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            z-index: 9999;
            transition: transform 0.3s ease;
        }
        
        .custom-toast.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .toast-content {
            background: white;
            border-radius: 50px;
            padding: 12px 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .toast-error .toast-content {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #DC2626;
        }
        
        .toast-success .toast-content {
            background: #DCFCE7;
            color: #166534;
            border-left: 4px solid #22C55E;
        }
        
        .toast-warning .toast-content {
            background: #FEF3C7;
            color: #92400E;
            border-left: 4px solid #F59E0B;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.6;
            padding: 0 4px;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        .search-loading {
            text-align: center;
            padding: 40px;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #D32F2F;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 16px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .toast-content {
                max-width: 90vw;
                font-size: 0.85rem;
                padding: 10px 18px;
            }
        }
    `;
    document.head.appendChild(style);
    
    // ==================== ОБРАБОТЧИКИ ДЛЯ КНОПОК ВЫБОРА МЕСТ ====================
    const seatButtons = document.querySelectorAll('.btn-select, .btn-buy');
    seatButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Добавляем эффект загрузки при клике
            if (this.tagName === 'A' && this.getAttribute('href')?.includes('booking')) {
                e.preventDefault();
                const href = this.getAttribute('href');
                showNotification('Перенаправление на оформление билета...', 'info');
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            }
        });
    });
    
    // ==================== ЗАПОЛНЕНИЕ ДАТЫ ПО УМОЛЧАНИЮ ====================
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            input.value = `${yyyy}-${mm}-${dd}`;
        }
        
        // Устанавливаем минимальную дату
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        input.min = `${yyyy}-${mm}-${dd}`;
    });
    
    // ==================== ЗАГРУЗКА ПОПУЛЯРНЫХ МАРШРУТОВ ====================
    loadPopularRoutes();
    
    // ==================== ФИКСИРОВАННАЯ ШАПКА ПРИ ПРОКРУТКЕ ====================
    let lastScrollTop = 0;
    const navbar = document.querySelector('.navbar');
    
    if (navbar) {
        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollTop > 100) {
                navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            } else {
                navbar.style.boxShadow = '0 1px 3px rgba(0,0,0,0.03), 0 1px 2px rgba(0,0,0,0.05)';
            }
            
            lastScrollTop = scrollTop;
        });
    }
    
    // ==================== ПОДСВЕТКА АКТИВНЫХ ССЫЛОК В НАВИГАЦИИ ====================
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-links a[href^="#"]');
    
    if (sections.length && navLinks.length) {
        window.addEventListener('scroll', () => {
            let current = '';
            const scrollPosition = window.scrollY + 100;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                const href = link.getAttribute('href').substring(1);
                if (href === current) {
                    link.classList.add('active');
                }
            });
        });
    }
    
    // Стили для активной ссылки
    const activeLinkStyle = document.createElement('style');
    activeLinkStyle.textContent = `
        .nav-links a.active {
            color: #D32F2F;
            font-weight: 700;
            position: relative;
        }
        
        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            right: 0;
            height: 2px;
            background: #D32F2F;
            border-radius: 2px;
        }
    `;
    document.head.appendChild(activeLinkStyle);
    
    // ==================== КНОПКА "НАВЕРХ" ====================
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollTopBtn.className = 'scroll-top-btn';
    scrollTopBtn.setAttribute('aria-label', 'Наверх');
    document.body.appendChild(scrollTopBtn);
    
    const scrollBtnStyle = document.createElement('style');
    scrollBtnStyle.textContent = `
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #D32F2F;
            color: white;
            border: none;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3);
            transition: all 0.3s ease;
            z-index: 99;
        }
        
        .scroll-top-btn:hover {
            background: #B71C1C;
            transform: scale(1.1);
        }
        
        .scroll-top-btn.show {
            display: flex;
        }
        
        @media (max-width: 768px) {
            .scroll-top-btn {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }
        }
    `;
    document.head.appendChild(scrollBtnStyle);
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollTopBtn.classList.add('show');
        } else {
            scrollTopBtn.classList.remove('show');
        }
    });
    
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // ==================== ЗАПУСК АНИМАЦИИ ПРИ ЗАГРУЗКЕ ====================
    setTimeout(animateOnScroll, 100);
    window.addEventListener('scroll', animateOnScroll);
    
    // ==================== ОБРАБОТКА ФОРМЫ БРОНИРОВАНИЯ ====================
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(bookingForm);
            const submitBtn = bookingForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Показываем загрузку
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Оформление...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('process-booking.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification(`Билет успешно оформлен! Номер: ${result.booking_number}`, 'success');
                    setTimeout(() => {
                        window.location.href = `ticket-info.php?booking=${result.booking_number}`;
                    }, 1500);
                } else {
                    showNotification(result.error || 'Ошибка при оформлении билета', 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Ошибка:', error);
                showNotification('Ошибка соединения. Попробуйте позже.', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    console.log('✅ main.js загружен, все функции инициализированы');
});