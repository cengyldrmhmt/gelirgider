<?php
require_once __DIR__ . '/../core/Database.php';

class ExchangeRate {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getRate($fromCurrency, $toCurrency = 'TRY') {
        try {
            $stmt = $this->db->prepare("
                SELECT rate, updated_at 
                FROM exchange_rates 
                WHERE from_currency = ? AND to_currency = ? 
                ORDER BY updated_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$fromCurrency, $toCurrency]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // If rate is older than 24 hours, update it
                $updatedAt = new DateTime($result['updated_at']);
                $now = new DateTime();
                $diff = $now->diff($updatedAt);
                
                // Günde bir kez güncelle (24 saat)
                if ($diff->days >= 1 || $diff->h >= 24) {
                    error_log("Rate is older than 24 hours, updating...");
                    $this->updateRatesFromAPI();
                    // Get updated rate
                    $stmt->execute([$fromCurrency, $toCurrency]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                return $result;
            } else {
                // No rate found, get from API
                error_log("No rate found for {$fromCurrency}/{$toCurrency}, fetching from API...");
                $this->updateRatesFromAPI();
                $stmt->execute([$fromCurrency, $toCurrency]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("ExchangeRate getRate error: " . $e->getMessage());
            return null;
        }
    }
    
    public function getAllRates() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM exchange_rates 
                WHERE to_currency = 'TRY' 
                ORDER BY updated_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("ExchangeRate getAllRates error: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateRatesFromAPI() {
        try {
            // Ana API: exchangerate-api.com (ücretsiz, günlük 1500 istek)
            $currencies = ['USD', 'EUR', 'GBP'];
            $updatedCount = 0;
            
            foreach ($currencies as $currency) {
                $rate = $this->fetchExchangeRateAPI($currency, 'TRY');
                if ($rate && $rate > 0) {
                    $this->saveRate($currency, 'TRY', $rate);
                    error_log("Updated {$currency}/TRY rate: {$rate}");
                    $updatedCount++;
                } else {
                    error_log("Failed to fetch {$currency}/TRY rate from primary API");
                    // Try Google as backup
                    $rate = $this->fetchGoogleRate($currency, 'TRY');
                    if ($rate && $rate > 0) {
                        $this->saveRate($currency, 'TRY', $rate);
                        error_log("Updated {$currency}/TRY rate from backup: {$rate}");
                        $updatedCount++;
                    }
                }
            }
            
            if ($updatedCount > 0) {
                error_log("Successfully updated {$updatedCount} exchange rates");
                return true;
            } else {
                error_log("No rates could be updated, using fallback");
                $this->saveFallbackRates();
                return false;
            }
            
        } catch (Exception $e) {
            error_log("ExchangeRate updateRatesFromAPI error: " . $e->getMessage());
            // Fallback static rates if API fails
            $this->saveFallbackRates();
            return false;
        }
    }
    
    private function fetchExchangeRateAPI($fromCurrency, $toCurrency) {
        try {
            // exchangerate-api.com - ücretsiz, güvenilir
            $url = "https://api.exchangerate-api.com/v4/latest/{$fromCurrency}";
            
            $context = stream_context_create([
                "http" => [
                    "timeout" => 15,
                    "method" => "GET",
                    "header" => [
                        "User-Agent: Mozilla/5.0 (compatible; Exchange Rate Bot)",
                        "Accept: application/json"
                    ]
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['rates'][$toCurrency])) {
                    $rate = floatval($data['rates'][$toCurrency]);
                    error_log("Fetched {$fromCurrency}/{$toCurrency} rate: {$rate} from exchangerate-api.com");
                    return $rate;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("exchangerate-api.com fetch error for {$fromCurrency}/{$toCurrency}: " . $e->getMessage());
            return false;
        }
    }
    
    private function fetchGoogleRate($fromCurrency, $toCurrency) {
        try {
            // Google Finance URL'si
            $url = "https://www.google.com/finance/quote/{$fromCurrency}-{$toCurrency}";
            
            // User-Agent ekleyerek gerçek bir tarayıcı gibi görünüyoruz
            $context = stream_context_create([
                "http" => [
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                ]
            ]);
            
            $html = @file_get_contents($url, false, $context);
            
            if ($html === false) {
                // Fallback: exchangerate-api.com (ücretsiz)
                return $this->fetchBackupRate($fromCurrency, $toCurrency);
            }
            
            // Google Finance'taki kur bilgisini regex ile çek
            if (preg_match('/data-last-price="([0-9,\.]+)"/', $html, $matches)) {
                $rate = str_replace(',', '', $matches[1]);
                return floatval($rate);
            }
            
            // Alternative regex pattern
            if (preg_match('/\["[^"]*","[^"]*","([0-9,\.]+)"/', $html, $matches)) {
                $rate = str_replace(',', '', $matches[1]);
                return floatval($rate);
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Google rate fetch error for {$fromCurrency}/{$toCurrency}: " . $e->getMessage());
            return $this->fetchBackupRate($fromCurrency, $toCurrency);
        }
    }
    
    private function fetchBackupRate($fromCurrency, $toCurrency) {
        try {
            // Backup API: exchangerate-api.com (ücretsiz, günlük 1500 istek)
            $url = "https://api.exchangerate-api.com/v4/latest/{$fromCurrency}";
            
            $context = stream_context_create([
                "http" => [
                    "timeout" => 10,
                    "header" => "User-Agent: Mozilla/5.0 (compatible; Exchange Rate Bot)\r\n"
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['rates'][$toCurrency])) {
                    return floatval($data['rates'][$toCurrency]);
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Backup rate fetch error: " . $e->getMessage());
            return false;
        }
    }
    
    private function saveRate($fromCurrency, $toCurrency, $rate) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO exchange_rates (from_currency, to_currency, rate, updated_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                rate = VALUES(rate), 
                updated_at = VALUES(updated_at)
            ");
            $stmt->execute([$fromCurrency, $toCurrency, $rate]);
        } catch (Exception $e) {
            error_log("ExchangeRate saveRate error: " . $e->getMessage());
        }
    }
    
    private function saveFallbackRates() {
        // Güncel fallback rates (Aralık 2024)
        $fallbackRates = [
            'USD' => 38.99,  // Güncel USD kuru
            'EUR' => 40.85,  // Güncel EUR kuru
            'GBP' => 49.20   // Güncel GBP kuru
        ];
        
        foreach ($fallbackRates as $currency => $rate) {
            $this->saveRate($currency, 'TRY', $rate);
        }
        
        error_log("Fallback rates saved successfully with updated values");
    }
    
    public function convertToTRY($amount, $fromCurrency) {
        if ($fromCurrency === 'TRY') {
            return $amount;
        }
        
        $rateData = $this->getRate($fromCurrency, 'TRY');
        if ($rateData && $rateData['rate'] > 0) {
            return $amount * $rateData['rate'];
        }
        
        // Fallback to static rates if API data is not available
        $fallbackRates = [
            'USD' => 38.99,  // Güncel USD kuru
            'EUR' => 40.85,  // Güncel EUR kuru
            'GBP' => 49.20   // Güncel GBP kuru
        ];
        
        if (isset($fallbackRates[$fromCurrency])) {
            error_log("Using fallback rate for {$fromCurrency}: {$fallbackRates[$fromCurrency]}");
            return $amount * $fallbackRates[$fromCurrency];
        }
        
        return $amount; // Return original if conversion fails
    }
    
    public function getFormattedRate($fromCurrency, $toCurrency = 'TRY') {
        $rateData = $this->getRate($fromCurrency, $toCurrency);
        if ($rateData) {
            return [
                'rate' => number_format($rateData['rate'], 4),
                'updated_at' => date('H:i', strtotime($rateData['updated_at']))
            ];
        }
        return null;
    }
    
    public function clearOldRates() {
        try {
            $stmt = $this->db->prepare("DELETE FROM exchange_rates WHERE to_currency = 'TRY'");
            $stmt->execute();
            error_log("Old exchange rates cleared successfully");
            return true;
        } catch (Exception $e) {
            error_log("Error clearing old rates: " . $e->getMessage());
            return false;
        }
    }
    
    public function forceUpdateRates() {
        // Eski kurları temizle ve zorla yeni kurları çek
        $this->clearOldRates();
        return $this->updateRatesFromAPI();
    }
} 