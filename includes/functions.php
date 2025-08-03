<?php
/**
 * فایل توابع کمکی پروژه دیکشنری سه زبانه
 */

/**
 * بررسی اتصال به اینترنت
 */
if (!function_exists('is_internet_connected')) {
    function is_internet_connected() {
        $connected = @fsockopen("www.google.com", 80, $errno, $errstr, 2);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }
}


/**
 * دریافت نام زبان بر اساس کد زبان
 */
function get_language_name($langCode, $displayLang = null) {
    global $lang;
    $displayLang = $displayLang ?? $lang;
    
    $names = [
        'en' => ['en' => 'English', 'fa' => 'انگلیسی', 'ar' => 'الإنجليزية'],
        'fa' => ['en' => 'Persian', 'fa' => 'فارسی', 'ar' => 'الفارسية'],
        'ar' => ['en' => 'Arabic', 'fa' => 'عربی', 'ar' => 'العربية'],
        'auto' => ['en' => 'Auto Detect', 'fa' => 'تشخیص خودکار', 'ar' => 'الكشف التلقائي'],
        'all' => ['en' => 'All', 'fa' => 'همه', 'ar' => 'الكل']
    ];
    
    return $names[$langCode][$displayLang] ?? $langCode;
}

/**
 * ایجاد گزینه‌های زبان مقصد برای تگ select
 */
function get_target_language_options($fromLang, $currentLang, $selectedLang) {
    $options = '';
    
    if ($fromLang === 'auto') {
        $languages = ['all', 'en', 'fa', 'ar'];
    } else {
        $languages = ['all'];
        $availableLangs = ['en', 'fa', 'ar'];
        foreach ($availableLangs as $lang) {
            if ($lang !== $fromLang) {
                $languages[] = $lang;
            }
        }
    }
    
    foreach ($languages as $langCode) {
        $selected = $langCode === $selectedLang ? 'selected' : '';
        $options .= "<option value='$langCode' $selected>" . get_language_name($langCode, $currentLang) . "</option>";
    }
    
    return $options;
}

/**
 * ایجاد URL برای جستجو
 */
function search_url($query, $fromLang, $toLang) {
    global $lang, $theme;
    $params = [
        'q' => urlencode($query),
        'from_lang' => urlencode($fromLang),
        'to_lang' => urlencode($toLang),
        'lang' => $lang,
        'theme' => $theme
    ];
    return base_url() . '?' . http_build_query($params);
}

/**
 * ایجاد URL برای تغییر زبان
 */
function change_lang_url() {
    global $lang, $theme;
    $newLang = $lang === 'fa' ? 'en' : ($lang === 'en' ? 'ar' : 'fa');
    return base_url() . '?' . http_build_query(['lang' => $newLang, 'theme' => $theme]);
}

/**
 * ایجاد URL برای تغییر تم
 */
function change_theme_url() {
    global $lang, $theme;
    $newTheme = $theme === 'light' ? 'dark' : 'light';
    return base_url() . '?' . http_build_query(['theme' => $newTheme, 'lang' => $lang]);
}

/**
 * نرمالایز کردن متن برای جستجو
 */
function normalize_search_text($text) {
    $text = trim($text);
    $text = mb_strtolower($text);
    $text = str_replace(' ', '', $text);
    
    // حذف علائم نگارشی
    $punctuations = ['!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/', ':', ';', '<', '=', '>', '?', '@', '[', '\\', ']', '^', '_', '`', '{', '|', '}', '~'];
    $text = str_replace($punctuations, '', $text);
    
    return $text;
}

/**
 * تشخیص جهت متن (RTL/LTR)
 */
function get_text_direction($text) {
    // الگوهای حروف فارسی و عربی
    $rtlPattern = '/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';
    
    if (preg_match($rtlPattern, $text)) {
        return 'rtl';
    }
    return 'ltr';
}

/**
 * ایجاد پیام خطای فرمت‌بندی شده
 */
function format_error_message($message, $type = 'error') {
    $classes = [
        'error' => 'text-red-600 bg-red-100 border-red-200',
        'warning' => 'text-yellow-600 bg-yellow-100 border-yellow-200',
        'success' => 'text-green-600 bg-green-100 border-green-200',
        'info' => 'text-blue-600 bg-blue-100 border-blue-200'
    ];
    
    $class = $classes[$type] ?? $classes['error'];
    
    return '<div class="p-3 mb-4 rounded border ' . $class . '">' . htmlspecialchars($message) . '</div>';
}

/**
 * رندر کردن نتایج جستجو به صورت HTML
 */
function render_search_results($results, $lang) {
    $html = '';
    
    foreach ($results as $direction => $items) {
        if (empty($items)) continue;
        
        list($src, $dst) = explode('-', $direction);
        $srcName = get_language_name($src, $lang);
        $dstName = get_language_name($dst, $lang);
        
        $html .= '<div class="result-section">';
        $html .= '<h2><i class="fas fa-arrow-right"></i> ' . htmlspecialchars("$srcName → $dstName") . '</h2>';
        
        foreach ($items as $item) {
            $html .= '<div class="result-item">';
            $html .= '<div class="source">' . htmlspecialchars($item[ucfirst($src).'Word']) . '</div>';
            $html .= '<div class="arrow"><i class="fas fa-long-arrow-alt-' . (in_array($lang, ['fa', 'ar']) ? 'left' : 'right') . '"></i></div>';
            $html .= '<div class="target">' . htmlspecialchars($item[ucfirst($dst).'Word']) . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    return $html;
}

/**
 * رندر کردن پیشنهادات جستجو
 */
function render_search_suggestions($suggestions, $lang) {
    if (empty($suggestions)) return '';
    
    global $t;
    
    $html = '<div class="suggestions">';
    $html .= '<h3><i class="fas fa-lightbulb"></i> ' . $t['suggestions'] . '</h3>';
    $html .= '<div class="suggestion-list">';
    
    foreach ($suggestions as $langCode => $items) {
        foreach ($items as $suggestion) {
            $html .= '<a href="' . search_url($suggestion, $langCode, 'all') . '" class="suggestion-item">';
            $html .= '<i class="fas fa-search"></i> ' . htmlspecialchars($suggestion);
            $html .= '</a>';
        }
    }
    
    $html .= '</div></div>';
    return $html;
}

/**
 * بررسی اعتبار کد زبان
 */
function is_valid_language_code($code) {
    return in_array($code, ['en', 'fa', 'ar', 'auto', 'all']);
}

/**
 * بررسی اعتبار نام تم
 */
function is_valid_theme($theme) {
    return in_array($theme, ['light', 'dark']);
}

/**
 * تبدیل جهت متن به کلاس CSS
 */
function get_direction_class($lang) {
    return in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr';
}

/**
 * ایجاد ساختار breadcrumb برای نتایج جستجو
 */
function generate_breadcrumb($query, $fromLang, $toLang, $lang) {
    $fromName = get_language_name($fromLang === 'auto' ? 'auto' : $fromLang, $lang);
    $toName = get_language_name($toLang === 'all' ? 'all' : $toLang, $lang);
    
    $breadcrumb = '<div class="breadcrumb">';
    $breadcrumb .= '<span>' . htmlspecialchars($query) . '</span>';
    $breadcrumb .= '<i class="fas fa-chevron-' . (in_array($lang, ['fa', 'ar']) ? 'left' : 'right') . '"></i>';
    $breadcrumb .= '<span>' . $fromName . '</span>';
    $breadcrumb .= '<i class="fas fa-chevron-' . (in_array($lang, ['fa', 'ar']) ? 'left' : 'right') . '"></i>';
    $breadcrumb .= '<span>' . $toName . '</span>';
    $breadcrumb .= '</div>';
    
    return $breadcrumb;
}