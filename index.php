<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_handler.php';
require_once __DIR__ . '/includes/google_translate.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize dictionary
$dbPath = __DIR__ . '/dictionaries/english_persian.sqlite';
$dictionary = new SmartDictionary($dbPath);

// Process search
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$fromLang = isset($_GET['from_lang']) ? $_GET['from_lang'] : 'auto';
$toLang = isset($_GET['to_lang']) ? $_GET['to_lang'] : 'all';
$showTranslate = isset($_GET['show_translate']) ? (bool)$_GET['show_translate'] : true;

// Search results
$results = [];
$suggestions = [];
$translationResults = [];
$detectedLang = null;

if (!empty($searchQuery)) {
    $detectedLang = $dictionary->detectLanguage($searchQuery);
    $results = $dictionary->search($searchQuery, $fromLang, $toLang);
    
    if (empty(array_filter($results))) {
        $suggestions = $dictionary->getSmartSuggestions($searchQuery);
    }
    
    if ($showTranslate) {
        $sourceLang = $fromLang === 'auto' ? $detectedLang : $fromLang;
        $targetLangs = ['en', 'fa', 'ar'];
        
        if (($key = array_search($sourceLang, $targetLangs)) !== false) {
            unset($targetLangs[$key]);
        }
        
        if ($toLang !== 'all') {
            $targetLangs = [$toLang];
        }
        
        $translationResults = get_translation_results($searchQuery, $sourceLang, $targetLangs);
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['title'] ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/themes/' . $theme . '.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= $theme ?>">
    <div class="container">
        <header>
            <h1><?= $t['title'] ?></h1>
            <p class="subtitle"><?= $t['subtitle'] ?></p>
            
            <div class="controls">
                <a href="<?= change_lang_url() ?>" class="lang-toggle">
                    <i class="fas fa-language"></i>
                    <?= $lang === 'fa' ? 'English' : ($lang === 'en' ? 'العربية' : 'فارسی') ?>
                </a>
                <a href="<?= change_theme_url() ?>" class="theme-toggle">
                    <i class="fas fa-<?= $theme === 'light' ? 'moon' : 'sun' ?>"></i>
                    <?= $theme === 'light' ? $t['toggle_dark'] : $t['toggle_light'] ?>
                </a>
            </div>
        </header>
        
        <form action="<?= base_url() ?>" method="get" class="search-form" id="searchForm">
            <input type="hidden" name="lang" value="<?= $lang ?>">
            <input type="hidden" name="theme" value="<?= $theme ?>">
            
            <div class="search-box">
                <input type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" 
                       placeholder="<?= $t['search_placeholder'] ?>" 
                       autocomplete="off" id="searchInput" autofocus>
                
                <div class="language-selector">
                    <select name="from_lang" id="fromLang">
                        <option value="auto" <?= $fromLang === 'auto' ? 'selected' : '' ?>><?= $t['auto_detect'] ?></option>
                        <option value="en" <?= $fromLang === 'en' ? 'selected' : '' ?>><?= $t['english'] ?></option>
                        <option value="fa" <?= $fromLang === 'fa' ? 'selected' : '' ?>><?= $t['persian'] ?></option>
                        <option value="ar" <?= $fromLang === 'ar' ? 'selected' : '' ?>><?= $t['arabic'] ?></option>
                    </select>
                    
                    <i class="fas fa-arrow-right"></i>
                    
                    <select name="to_lang" id="toLang">
                        <?= get_target_language_options($fromLang, $lang, $toLang) ?>
                    </select>
                    
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> <?= $t['search'] ?>
                    </button>
                </div>
                
                <div class="translate-toggle">
                    <button type="submit" name="show_translate" value="<?= $showTranslate ? '0' : '1' ?>" class="translate-btn <?= $showTranslate ? 'active' : '' ?>">
                        <i class="fas fa-google"></i>
                        <?= $t['toggle_translate'] ?>
                    </button>
                </div>
            </div>
        </form>

        <?php if ($fromLang === 'auto' && !empty($searchQuery)): ?>
            <div class="alert">
                <i class="fas fa-info-circle"></i>
                <p><?= $t['auto_search_notice'] ?></p>
            </div>
        <?php endif; ?>

        <div class="results-container" id="resultsContainer">
            <?php if (!empty($searchQuery)): ?>
                <?php if (!empty(array_filter($results))): ?>
                    <?php foreach ($results as $direction => $items): ?>
                        <?php if (!empty($items)): ?>
                            <?php 
                            list($src, $dst) = explode('-', $direction);
                            $srcName = get_language_name($src, $lang);
                            $dstName = get_language_name($dst, $lang);
                            ?>
                            <div class="result-section">
                                <h2>
                                    <span>
                                        <i class="fas fa-arrow-right"></i>
                                        <?= "$srcName → $dstName" ?>
                                    </span>
                                    <?php if ($showTranslate && (!empty($translationResults['google']) || !empty($translationResults['machine']))): ?>
                                        <a href="#google-translate" class="jump-to-translate">
                                            <i class="fas fa-arrow-down"></i> <?= $lang === 'fa' ? 'برو به ترجمه' : ($lang === 'ar' ? 'انتقل إلى الترجمة' : 'Jump to Translation') ?>
                                        </a>
                                    <?php endif; ?>
                                </h2>
                                <?php foreach ($items as $item): ?>
                                    <div class="result-item">
                                        <div class="source"><?= $item[ucfirst($src).'Word'] ?></div>
                                        <div class="arrow"><i class="fas fa-long-arrow-alt-<?= in_array($lang, ['fa', 'ar']) ? 'left' : 'right' ?>"></i></div>
                                        <div class="target"><?= $item[ucfirst($dst).'Word'] ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="far fa-frown"></i>
                        <p><?= sprintf($t['no_results'], htmlspecialchars($searchQuery)) ?></p>
                        
                        <?php if (!empty($suggestions)): ?>
                            <div class="suggestions">
                                <h3><i class="fas fa-lightbulb"></i> <?= $t['suggestions'] ?></h3>
                                <div class="suggestion-list">
                                    <?php foreach ($suggestions as $langCode => $items): ?>
                                        <?php foreach ($items as $suggestion): ?>
                                            <a href="<?= search_url($suggestion, $langCode, 'all') ?>" class="suggestion-item">
                                                <i class="fas fa-search"></i> <?= $suggestion ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- بخش ترجمه گوگل -->
                <?php if ($showTranslate && !empty($translationResults['google'])): ?>
                    <div class="translate-section" id="google-translate">
                        <h2>
                            <i class="fab fa-google"></i>
                            <?= $t['google_translate'] ?>
                        </h2>
                        
                        <?php foreach ($translationResults['google'] as $targetLang => $translation): ?>
                            <?php if (!empty($translation)): ?>
                                <?php 
                                $sourceLang = $fromLang === 'auto' ? $detectedLang : $fromLang;
                                $srcName = get_language_name($sourceLang, $lang);
                                $dstName = get_language_name($targetLang, $lang);
                                ?>
                                <div class="translate-item">
                                    <div class="source"><?= htmlspecialchars($searchQuery) ?> (<?= $srcName ?>)</div>
                                    <div class="arrow"><i class="fas fa-long-arrow-alt-<?= in_array($lang, ['fa', 'ar']) ? 'left' : 'right' ?>"></i></div>
                                    <div class="target"><?= htmlspecialchars($translation) ?> (<?= $dstName ?>)</div>
                                    <div class="engine-badge">Google</div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="translate-notice">
                            <i class="fas fa-info-circle"></i>
                            <?= $t['translate_notice'] ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- بخش ترجمه هوشمصنوعی -->
                <?php if ($showTranslate && !empty($translationResults['machine'])): ?>
                    <div class="translate-section ai-translate-section" id="ai-translate">
                        <h2>
                            <i class="fas fa-robot"></i>
                            <?= $t['ai_translate'] ?>
                        </h2>
                        
                        <?php foreach ($translationResults['machine'] as $targetLang => $translation): ?>
                            <?php if (!empty($translation)): ?>
                                <?php 
                                $sourceLang = $fromLang === 'auto' ? $detectedLang : $fromLang;
                                $srcName = get_language_name($sourceLang, $lang);
                                $dstName = get_language_name($targetLang, $lang);
                                ?>
                                <div class="translate-item">
                                    <div class="source"><?= htmlspecialchars($searchQuery) ?> (<?= $srcName ?>)</div>
                                    <div class="arrow"><i class="fas fa-long-arrow-alt-<?= in_array($lang, ['fa', 'ar']) ? 'left' : 'right' ?>"></i></div>
                                    <div class="target"><?= htmlspecialchars($translation) ?> (<?= $dstName ?>)</div>
                                    <div class="engine-badge">AI Translation</div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="translate-notice">
                            <i class="fas fa-info-circle"></i>
                            <?= $t['ai_translate_notice'] ?>
                        </div>
                    </div>
                <?php elseif ($showTranslate && !is_internet_connected()): ?>
                    <div class="translate-offline">
                        <i class="fas fa-wifi-slash"></i>
                        <?= $t['translate_offline'] ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="welcome-message">
                    <i class="fas fa-search"></i>
                    <p><?= $t['welcome'] ?></p>
                    <div class="tips">
                        <h3><i class="fas fa-tips"></i> <?= $t['tips'] ?></h3>
                        <ul>
                            <li><i class="fas fa-check-circle"></i> <?= $t['tip1'] ?></li>
                            <li><i class="fas fa-check-circle"></i> <?= $t['tip2'] ?></li>
                            <li><i class="fas fa-check-circle"></i> <?= $t['tip3'] ?></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <footer class="creator-footer">
            <p><?= $t['creator'] ?></p>
            <div class="social-links">
                <a href="#" target="_blank"><i class="fab fa-github"></i></a>
                <a href="#" target="_blank"><i class="fab fa-linkedin"></i></a>
                <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
            </div>
        </footer>
    </div>

    <script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>