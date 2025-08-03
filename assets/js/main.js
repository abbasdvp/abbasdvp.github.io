/**
 * فایل اصلی جاوااسکریپت برای دیکشنری سه زبانه
 * شامل تمام توابع و منطق تعاملی صفحه
 */

document.addEventListener('DOMContentLoaded', function() {
    // انتخاب عناصر DOM
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('resultsContainer');
    const fromLangSelect = document.getElementById('fromLang');
    const toLangSelect = document.getElementById('toLang');
    const searchBtn = document.querySelector('.search-btn');
    const langToggle = document.querySelector('.lang-toggle');
    const themeToggle = document.querySelector('.theme-toggle');
    
    // متغیرهای حالت
    let searchTimeout;
    let isManualSearch = false;
    let currentUrlParams = new URLSearchParams(window.location.search);

    // دریافت ترجمه‌ها از دیتای صفحه
    function getTranslations() {
        try {
            const translationsElement = document.getElementById('translations');
            if (translationsElement) {
                return JSON.parse(translationsElement.textContent);
            }
            return {};
        } catch (e) {
            console.error('Error parsing translations:', e);
            return {};
        }
    }

    const translations = getTranslations();

    // دریافت ترجمه متن
    function getTranslation(key) {
        return translations[key] || key;
    }

    // دریافت نام زبان
    function getLanguageName(langCode) {
        const languageNames = {
            'en': { 'en': 'English', 'fa': 'انگلیسی', 'ar': 'الإنجليزية' },
            'fa': { 'en': 'Persian', 'fa': 'فارسی', 'ar': 'الفارسية' },
            'ar': { 'en': 'Arabic', 'fa': 'عربی', 'ar': 'العربية' },
            'auto': { 'en': 'Auto Detect', 'fa': 'تشخیص خودکار', 'ar': 'الكشف التلقائي' },
            'all': { 'en': 'All', 'fa': 'همه', 'ar': 'الكل' }
        };
        
        const currentLang = document.documentElement.lang;
        return languageNames[langCode]?.[currentLang] || langCode;
    }

    // تنظیم اولیه مقادیر فرم بر اساس URL
    function initializeFormFromUrl() {
        const params = new URLSearchParams(window.location.search);
        
        if (params.has('q')) {
            searchInput.value = params.get('q');
        }
        
        if (params.has('from_lang')) {
            fromLangSelect.value = params.get('from_lang');
        }
        
        if (params.has('to_lang')) {
            toLangSelect.value = params.get('to_lang');
        }
        
        updateTargetLanguages();
    }

    // به‌روزرسانی گزینه‌های زبان مقصد بر اساس زبان مبدأ
    function updateTargetLanguages() {
        const fromLang = fromLangSelect.value;
        let options = '';
        
        if (fromLang === 'auto') {
            options = `
                <option value="all">${getTranslation('all_results')}</option>
                <option value="en">${getTranslation('english')}</option>
                <option value="fa">${getTranslation('persian')}</option>
                <option value="ar">${getTranslation('arabic')}</option>
            `;
        } else {
            options = `<option value="all">${getTranslation('all_results')}</option>`;
            const availableLangs = ['en', 'fa', 'ar'];
            
            availableLangs.forEach(lang => {
                if (lang !== fromLang) {
                    options += `<option value="${lang}">${getLanguageName(lang)}</option>`;
                }
            });
        }
        
        toLangSelect.innerHTML = options;
        
        // حفظ مقدار انتخاب شده قبلی اگر وجود داشته باشد
        const params = new URLSearchParams(window.location.search);
        if (params.has('to_lang')) {
            const toLang = params.get('to_lang');
            if (toLangSelect.querySelector(`option[value="${toLang}"]`)) {
                toLangSelect.value = toLang;
            }
        }
    }

    // نمایش حالت لودینگ
    function showLoadingState() {
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                    <p>${getTranslation('searching')}</p>
                </div>
            `;
        }
    }

    // مدیریت خطای جستجو
    function handleSearchError(error) {
        console.error('Search error:', error);
        
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="welcome-message">
                    <i class="fas fa-search"></i>
                    <p>${getTranslation('search_error')}</p>
                </div>
            `;
        }
    }

    // انجام جستجو
    async function performSearch() {
        if (!searchForm || !resultsContainer) return;
        
        try {
            const formData = new FormData(searchForm);
            const params = new URLSearchParams(formData);
            
            // نمایش حالت لودینگ
            showLoadingState();
            
            // ارسال درخواست AJAX
            const response = await fetch(`${searchForm.action}?${params.toString()}&ajax=1`);
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const responseData = await response.json();
            
            if (responseData.html) {
                // نمایش نتایج
                resultsContainer.innerHTML = responseData.html;
                
                // به‌روزرسانی URL بدون رفرش صفحه
                params.delete('ajax');
                history.pushState(null, null, `?${params.toString()}`);
            } else {
                throw new Error('Invalid response format');
            }
            
        } catch (error) {
            console.error('Error fetching results:', error);
            handleSearchError(error);
        } finally {
            isManualSearch = false;
        }
    }

    // جستجوی خودکار هنگام تایپ
    function setupLiveSearch() {
        if (!searchInput) return;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            if (this.value.trim().length > 0) {
                searchTimeout = setTimeout(() => {
                    performSearch().catch(e => console.error('Search error:', e));
                }, 500);
            } else {
                showWelcomeMessage();
            }
        });
    }

    // نمایش پیام خوش‌آمدگویی
    function showWelcomeMessage() {
        if (resultsContainer) {
            resultsContainer.innerHTML = `
                <div class="welcome-message">
                    <i class="fas fa-search"></i>
                    <p>${getTranslation('welcome')}</p>
                </div>
            `;
        }
    }

    // مدیریت تغییرات تاریخچه مرورگر
    function setupHistoryManagement() {
        window.addEventListener('popstate', function() {
            const params = new URLSearchParams(window.location.search);
            const query = params.get('q') || '';
            
            if (searchInput) {
                searchInput.value = query;
            }
            
            if (query.trim().length > 0) {
                performSearch().catch(e => console.error('Search error:', e));
            } else {
                showWelcomeMessage();
            }
        });
    }

    // تنظیم رویدادها
    function setupEventListeners() {
        // جستجوی دستی
        if (searchBtn) {
            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                isManualSearch = true;
                performSearch();
            });
        }
        
        // ارسال فرم
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                isManualSearch = true;
                performSearch();
            });
        }
        
        // تغییر زبان مبدأ
        if (fromLangSelect) {
            fromLangSelect.addEventListener('change', updateTargetLanguages);
        }
        
        // تغییر زبان از طریق دکمه
        if (langToggle) {
            langToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const newLang = this.getAttribute('href').split('lang=')[1].split('&')[0];
                window.location.href = `?lang=${newLang}&theme=${currentUrlParams.get('theme') || 'light'}`;
            });
        }
        
        // تغییر تم از طریق دکمه
        if (themeToggle) {
            themeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                const newTheme = this.getAttribute('href').split('theme=')[1].split('&')[0];
                window.location.href = `?theme=${newTheme}&lang=${currentUrlParams.get('lang') || 'fa'}`;
            });
        }
        
        // فوکوس سریع به فیلد جستجو با کلید /
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && e.target !== searchInput && searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // مقداردهی اولیه
    function initialize() {
        initializeFormFromUrl();
        setupLiveSearch();
        setupHistoryManagement();
        setupEventListeners();
        
        // اگر عبارت جستجو در URL وجود دارد، جستجو انجام شود
        if (currentUrlParams.has('q') && currentUrlParams.get('q').trim() !== '') {
            isManualSearch = true;
            performSearch();
        }
    }

    // شروع اجرای اسکریپت
    initialize();
});