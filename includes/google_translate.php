<?php
/**
 * فایل مدیریت ترجمه گوگل و سرویس‌های دیگر
 */

function get_google_translate_result($text, $sourceLang, $targetLang) {
    // بررسی اتصال به اینترنت
    if (!is_internet_connected()) {
        return false;
    }

    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl={$sourceLang}&tl={$targetLang}&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data[0][0][0])) {
        return $data[0][0][0];
    }
    
    return false;
}

/**
 * تابع جدید برای استفاده از API ماشین ترجمه
 */
function get_machine_translation_result($text, $sourceLang, $targetLang) {
    if (!is_internet_connected()) {
        return false;
    }

    $apiKey = 'YOUR_API_KEY'; // باید API Key خود را از MachineTranslation.com دریافت کنید
    $url = 'https://api.machinetranslation.com/pv1/translate';
    
    $data = [
        'text' => $text,
        'source_language_code' => $sourceLang,
        'target_language_code' => $targetLang
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: BEARER ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response === false) {
        return false;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['translations'][0]['target_text'])) {
        return $result['translations'][0]['target_text'];
    }
    
    return false;
}

function get_translation_results($text, $sourceLang, $targetLangs) {
    $results = [];
    
    // ترجمه گوگل
    foreach ($targetLangs as $lang) {
        if ($lang !== $sourceLang) {
            $results['google'][$lang] = get_google_translate_result($text, $sourceLang, $lang);
        }
    }
    
    // ترجمه ماشین (MachineTranslation.com)
    foreach ($targetLangs as $lang) {
        if ($lang !== $sourceLang) {
            $results['machine'][$lang] = get_machine_translation_result($text, $sourceLang, $lang);
        }
    }
    
    return $results;
}
?>