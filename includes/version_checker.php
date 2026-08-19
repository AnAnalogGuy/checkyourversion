<?php
/**
 * Version Checker - Integration with endoflife.date API v1
 * 
 * API Documentation: https://endoflife.date/docs/api/v1/
 * 
 * Key endpoints:
 * - GET /api/v1/products → List all products
 * - GET /api/v1/products/{product} → Get product details with cycles
 * 
 * Product structure:
 * {
 *   "latest": "string",
 *   "latestReleaseDate": "2024-01-01",
 *   "cycles": [
 *     {
 *       "cycle": "22H2",
 *       "releaseDate": "2024-01-01",
 *       "eol": "2024-12-31",
 *       "lts": true
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/config.php';

class VersionChecker {
    
    /**
     * Get all available products
     * 
     * @return array|null
     */
    public static function getAllProducts() {
        $url = ENDOFLIFE_PRODUCTS_ENDPOINT;
        
        $response = self::makeRequest($url);
        
        if (!$response || !isset($response['products'])) {
            return null;
        }
        
        return $response['products'];
    }
    
    /**
     * Search for software on endoflife.date
     * 
     * @param string $softwareName
     * @return array|null Product info or null
     */
    public static function searchSoftware($softwareName) {
        $products = self::getAllProducts();
        
        if (!$products) {
            return null;
        }
        
        $needle = strtolower($softwareName);
        
        foreach ($products as $product) {
            $name = $product['name'] ?? $product ?? '';
            
            if (stripos($name, $needle) !== false) {
                // Return the product identifier
                return ['id' => $name, 'name' => $name];
            }
        }
        
        return null;
    }
    
    /**
     * Get version information for a specific software
     * 
     * @param string $productId (e.g., "windows-10")
     * @return array|null
     */
    public static function getVersionInfo($productId) {
        // Normalize product ID
        $productId = strtolower(str_replace(' ', '-', trim($productId)));
        
        $url = ENDOFLIFE_PRODUCT_ENDPOINT . '/' . urlencode($productId);
        
        $response = self::makeRequest($url);
        
        if (!$response) {
            return null;
        }
        
        return $response;
    }
    
    /**
     * Check if a version is current/up-to-date
     * 
     * @param string $productId
     * @param string $userVersion
     * @return array with status and details
     */
    public static function checkVersion($productId, $userVersion) {
        $versionData = self::getVersionInfo($productId);
        
        if (!$versionData) {
            return [
                'status' => 'error',
                'message' => 'Software not found in endoflife.date database',
                'currentVersion' => null,
                'userVersion' => $userVersion,
                'isUpToDate' => null,
                'eolDate' => null,
                'isEOL' => null
            ];
        }
        
        // Get latest version from cycles
        $cycles = $versionData['cycles'] ?? [];
        $latestVersion = $versionData['latest'] ?? null;
        
        // Find the cycle that matches the user's version
        $userCycle = null;
        foreach ($cycles as $cycleData) {
            $cycleName = $cycleData['cycle'] ?? '';
            
            if ($cycleName === $userVersion || stripos($cycleName, $userVersion) !== false) {
                $userCycle = $cycleData;
                break;
            }
        }
        
        // If no exact match found, try to find by version field
        if (!$userCycle) {
            foreach ($cycles as $cycleData) {
                $version = $cycleData['version'] ?? '';
                if ($version === $userVersion) {
                    $userCycle = $cycleData;
                    break;
                }
            }
        }
        
        if (!$userCycle) {
            return [
                'status' => 'error',
                'message' => "Version '$userVersion' not found",
                'currentVersion' => $latestVersion,
                'userVersion' => $userVersion,
                'isUpToDate' => null,
                'eolDate' => null,
                'isEOL' => null
            ];
        }
        
        // Check EOL status
        $eolDate = $userCycle['eol'] ?? null;
        $isEOL = $eolDate ? (strtotime($eolDate) < time()) : false;
        
        // Compare versions (simple string comparison, works for semantic versioning)
        $isUpToDate = version_compare($userVersion, $latestVersion ?? '0', '>=');
        
        return [
            'status' => 'success',
            'currentVersion' => $latestVersion,
            'userVersion' => $userVersion,
            'isUpToDate' => $isUpToDate,
            'eolDate' => $eolDate,
            'isEOL' => $isEOL,
            'releaseDate' => $userCycle['releaseDate'] ?? $userCycle['release'] ?? null,
            'supportStatus' => $isEOL ? 'End of Life' : 'Supported',
            'lts' => $userCycle['lts'] ?? false
        ];
    }
    
    /**
     * Make HTTP request to API
     * 
     * @param string $url
     * @return array|null
     */
    private static function makeRequest($url) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: ' . USER_AGENT
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("CURL Error: $curlError");
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("HTTP Error: $httpCode for URL: $url");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (!$data) {
            error_log("JSON Decode Error for URL: $url");
            return null;
        }
        
        return $data;
    }
}
?>