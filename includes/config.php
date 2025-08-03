<?php
// تنظیمات نمایش خطاها
error_reporting(E_ALL);
ini_set('display_errors', 1);

// شروع سشن
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تابع برای ایجاد URL پایه
function base_url($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
    return $protocol . $host . $base . ltrim($path, '/');
}

// تنظیم زبان پیش‌فرض
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'fa'; // فارسی پیش‌فرض
}

// تغییر زبان اگر درخواست شده باشد
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = in_array($_GET['lang'], ['fa', 'en', 'ar']) ? $_GET['lang'] : 'fa';
}

// تنظیم تم پیش‌فرض
if (!isset($_SESSION['theme'])) {
    $_SESSION['theme'] = 'light';
}

// تغییر تم اگر درخواست شده باشد
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = in_array($_GET['theme'], ['light', 'dark']) ? $_GET['theme'] : 'light';
}

// متغیرهای جهانی
$lang = $_SESSION['lang'];
$theme = $_SESSION['theme'];

// ترجمه‌ها
$translations = [
    'fa' => [
        'title' => 'دیکشنری هوشمند سه زبانه',
        'subtitle' => 'ترجمه انگلیسی ↔ فارسی ↔ عربی',
        'search_placeholder' => 'کلمه یا عبارت مورد نظر...',
        'from_lang' => 'زبان مبدأ',
        'to_lang' => 'زبان مقصد',
        'auto_detect' => 'تشخیص خودکار',
        'all_results' => 'همه نتایج',
        'english' => 'انگلیسی',
        'persian' => 'فارسی',
        'arabic' => 'عربی',
        'search' => 'جستجو',
        'auto_search_notice' => '⚠️ جستجوی خودکار فعال است. نتایج بر اساس زبان تشخیص داده شده نمایش داده می‌شود.',
        'no_results' => 'نتیجه‌ای برای "%s" یافت نشد.',
        'suggestions' => 'پیشنهادات:',
        'welcome' => 'کلمه یا عبارت مورد نظر خود را برای ترجمه وارد کنید.',
        'tips' => 'راهنما:',
        'tip1' => 'برای جستجوی دقیق، زبان مبدأ و مقصد را انتخاب کنید',
        'tip2' => 'از گزینه "تشخیص خودکار" برای جستجوی هوشمند استفاده کنید',
        'tip3' => 'نیازی به رعایت فاصله یا حروف بزرگ/کوچک نیست',
        'creator' => 'طراحی و توسعه توسط سید عباس داورپناه',
        'toggle_dark' => 'تم تاریک',
        'toggle_light' => 'تم روشن',
        'toggle_translate' => 'نمایش ترجمه گوگل',
        'google_translate' => 'ترجمه گوگل',
        'translate_notice' => 'این نتایج از سرویس ترجمه گوگل دریافت شده‌اند',
        'translate_offline' => 'اتصال به اینترنت برای ترجمه گوگل وجود ندارد',
        'searching' => 'در حال جستجو...',
        'search_error' => 'خطا در انجام جستجو'
    ],
    'en' => [
        'title' => 'Smart Trilingual Dictionary',
        'subtitle' => 'English ↔ Persian ↔ Arabic Translation',
        'search_placeholder' => 'Enter word or phrase...',
        'from_lang' => 'Source Language',
        'to_lang' => 'Target Language',
        'auto_detect' => 'Auto Detect',
        'all_results' => 'All Results',
        'english' => 'English',
        'persian' => 'Persian',
        'arabic' => 'Arabic',
        'search' => 'Search',
        'auto_search_notice' => '⚠️ Auto detection is active. Results are shown based on detected language.',
        'no_results' => 'No results found for "%s".',
        'suggestions' => 'Suggestions:',
        'welcome' => 'Enter a word or phrase to translate.',
        'tips' => 'Tips:',
        'tip1' => 'Select source and target languages for precise search',
        'tip2' => 'Use "Auto Detect" for smart search',
        'tip3' => 'No need to worry about spaces or case sensitivity',
        'creator' => 'Designed and developed by Seyed Abbas Davarpanah',
        'toggle_dark' => 'Dark mode',
        'toggle_light' => 'Light mode',
        'toggle_translate' => 'Show Google Translate',
        'google_translate' => 'Google Translate',
        'translate_notice' => 'These results are from Google Translate service',
        'translate_offline' => 'Internet connection is required for Google Translate',
        'searching' => 'Searching...',
        'search_error' => 'Error performing search'
    ],
    'ar' => [
        'title' => 'القاموس الذكي ثلاثي اللغات',
        'subtitle' => 'ترجمة إنجليزية ↔ فارسية ↔ عربية',
        'search_placeholder' => 'أدخل الكلمة أو العبارة...',
        'from_lang' => 'لغة المصدر',
        'to_lang' => 'لغة الهدف',
        'auto_detect' => 'الكشف التلقائي',
        'all_results' => 'جميع النتائج',
        'english' => 'الإنجليزية',
        'persian' => 'الفارسية',
        'arabic' => 'العربية',
        'search' => 'بحث',
        'auto_search_notice' => '⚠️ الكشف التلقائي نشط. يتم عرض النتائج بناءً على اللغة المكتشفة.',
        'no_results' => 'لم يتم العثور على نتائج لـ "%s".',
        'suggestions' => 'اقتراحات:',
        'welcome' => 'أدخل كلمة أو عبارة للترجمة.',
        'tips' => 'نصائح:',
        'tip1' => 'حدد لغتي المصدر والهدف للبحث الدقيق',
        'tip2' => 'استخدم "الكشف التلقائي" للبحث الذكي',
        'tip3' => 'لا داعي للقلق بشأن المسافات أو حالة الأحرف',
        'creator' => 'صمم وطوره سيد عباس داورباناه',
        'toggle_dark' => 'الوضع المظلم',
        'toggle_light' => 'الوضع الفاتح',
        'toggle_translate' => 'عرض ترجمة جوجل',
        'google_translate' => 'ترجمة جوجل',
        'translate_notice' => 'هذه النتائج من خدمة ترجمة جوجل',
        'translate_offline' => 'الاتصال بالإنترنت مطلوب لترجمة جوجل',
        'searching' => 'جاري البحث...',
        'search_error' => 'خطأ في إجراء البحث'
    ]
];

// متغیر ترجمه‌ها
$t = $translations[$lang];
?>