<?php
/**
 * کلاس مدیریت دیتابیس دیکشنری سه زبانه
 */
class SmartDictionary {
    private $db;
    private $tableName = "EnglishPersianWordDatabase_First Sheet"; // نام جدول با فاصله
    
    public function __construct($dbPath) {
        try {
            // اتصال به دیتابیس SQLite
            $this->db = new PDO("sqlite:" . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // بررسی وجود جدول
            $this->verifyDatabaseStructure();
        } catch (PDOException $e) {
            die("خطا در اتصال به دیتابیس: " . $e->getMessage());
        }
    }

    /**
     * بررسی ساختار دیتابیس و وجود جدول و ستون‌های مورد نیاز
     */
    private function verifyDatabaseStructure() {
        try {
            // بررسی وجود جدول
            $stmt = $this->db->prepare("SELECT 1 FROM `{$this->tableName}` LIMIT 1");
            $stmt->execute();
            
            // بررسی وجود ستون‌های ضروری
            $requiredColumns = ['EnglishWord', 'PersianWord', 'ArabinEnWord', 'ArabinFaWord'];
            $stmt = $this->db->prepare("PRAGMA table_info(`{$this->tableName}`)");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    throw new PDOException("ستون '{$column}' در جدول وجود ندارد");
                }
            }
        } catch (PDOException $e) {
            die("مشکل در ساختار دیتابیس: " . $e->getMessage());
        }
    }

    /**
     * تشخیص زبان متن ورودی
     */
    public function detectLanguage($text) {
        $text = trim($text);
        if (empty($text)) return 'en';
        
        // تشخیص فارسی یا عربی
        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
            // تشخیص فارسی (دارای اعداد فارسی)
            return (preg_match('/[\x{06F0}-\x{06F9}]/u', $text)) ? 'fa' : 'ar';
        }
        return 'en';
    }

    /**
     * جستجوی کلمه در دیتابیس
     */
    public function search($query, $sourceLang = 'auto', $targetLang = 'all', $limit = 50) {
        $query = trim($query);
        if (empty($query)) return [];
        
        $normalizedQuery = $this->normalize($query);
        $results = [];
        
        // تشخیص خودکار زبان
        if ($sourceLang === 'auto') {
            $sourceLang = $this->detectLanguage($query);
        }
        
        // جستجو بر اساس زبان مبدأ
        switch ($sourceLang) {
            case 'en':
                $results = $this->searchFromEnglish($query, $normalizedQuery, $targetLang, $limit);
                break;
            case 'fa':
                $results = $this->searchFromPersian($query, $normalizedQuery, $targetLang, $limit);
                break;
            case 'ar':
                $results = $this->searchFromArabic($query, $normalizedQuery, $targetLang, $limit);
                break;
        }
        
        return $results;
    }

    /**
     * جستجو وقتی زبان مبدأ انگلیسی است
     */
    private function searchFromEnglish($query, $normalizedQuery, $targetLang, $limit) {
        $results = [];
        
        if ($targetLang === 'all' || $targetLang === 'fa') {
            $stmt = $this->db->prepare("
                SELECT EnglishWord AS EnWord, PersianWord AS FaWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(EnglishWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN EnglishWord LIKE :query_exact THEN 0
                        WHEN EnglishWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(EnglishWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['en-fa'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($targetLang === 'all' || $targetLang === 'ar') {
            $stmt = $this->db->prepare("
                SELECT EnglishWord AS EnWord, ArabinEnWord AS ArWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(EnglishWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN EnglishWord LIKE :query_exact THEN 0
                        WHEN EnglishWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(EnglishWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['en-ar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $results;
    }

    /**
     * جستجو وقتی زبان مبدأ فارسی است
     */
    private function searchFromPersian($query, $normalizedQuery, $targetLang, $limit) {
        $results = [];
        
        if ($targetLang === 'all' || $targetLang === 'en') {
            $stmt = $this->db->prepare("
                SELECT PersianWord AS FaWord, EnglishWord AS EnWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(PersianWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN PersianWord LIKE :query_exact THEN 0
                        WHEN PersianWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(PersianWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['fa-en'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($targetLang === 'all' || $targetLang === 'ar') {
            $stmt = $this->db->prepare("
                SELECT PersianWord AS FaWord, ArabinFaWord AS ArWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(PersianWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN PersianWord LIKE :query_exact THEN 0
                        WHEN PersianWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(PersianWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['fa-ar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $results;
    }

    /**
     * جستجو وقتی زبان مبدأ عربی است
     */
    private function searchFromArabic($query, $normalizedQuery, $targetLang, $limit) {
        $results = [];
        
        if ($targetLang === 'all' || $targetLang === 'en') {
            $stmt = $this->db->prepare("
                SELECT ArabinEnWord AS ArWord, EnglishWord AS EnWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(ArabinEnWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN ArabinEnWord LIKE :query_exact THEN 0
                        WHEN ArabinEnWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(ArabinEnWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['ar-en'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if ($targetLang === 'all' || $targetLang === 'fa') {
            $stmt = $this->db->prepare("
                SELECT ArabinFaWord AS ArWord, PersianWord AS FaWord 
                FROM `{$this->tableName}`
                WHERE LOWER(REPLACE(ArabinFaWord, ' ', '')) LIKE :query
                ORDER BY 
                    CASE 
                        WHEN ArabinFaWord LIKE :query_exact THEN 0
                        WHEN ArabinFaWord LIKE :query_start THEN 1
                        ELSE 2
                    END,
                    LENGTH(ArabinFaWord)
                LIMIT :limit
            ");
            $stmt->execute([
                ':query' => "%$normalizedQuery%",
                ':query_exact' => $query,
                ':query_start' => "$query%",
                ':limit' => $limit
            ]);
            $results['ar-fa'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $results;
    }

    /**
     * دریافت پیشنهادات هوشمند برای جستجو
     */
    public function getSmartSuggestions($query, $limit = 5) {
        $normalizedQuery = $this->normalize($query);
        $suggestions = [];
        $detectedLang = $this->detectLanguage($query);
        
        switch ($detectedLang) {
            case 'en':
                $column = 'EnglishWord';
                break;
            case 'fa':
                $column = 'PersianWord';
                break;
            default:
                $column = 'ArabinEnWord';
        }
        
        $stmt = $this->db->prepare("
            SELECT $column 
            FROM `{$this->tableName}`
            WHERE LOWER(REPLACE($column, ' ', '')) LIKE :query
            ORDER BY 
                CASE 
                    WHEN $column LIKE :query_start THEN 0
                    ELSE 1
                END,
                LENGTH($column)
            LIMIT :limit
        ");
        $stmt->execute([
            ':query' => "%$normalizedQuery%",
            ':query_start' => "$normalizedQuery%",
            ':limit' => $limit
        ]);
        
        $suggestions[$detectedLang] = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        return $suggestions;
    }

    /**
     * نرمالایز کردن متن برای جستجو
     */
    private function normalize($str) {
        $str = mb_strtolower($str);
        $str = str_replace(' ', '', $str);
        
        // حذف علائم نگارشی
        $punctuations = ['!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/', ':', ';', '<', '=', '>', '?', '@', '[', '\\', ']', '^', '_', '`', '{', '|', '}', '~'];
        $str = str_replace($punctuations, '', $str);
        
        return $str;
    }
}
?>