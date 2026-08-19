<?php
/**
 * CVE Fetcher - Integration with NVD API 2.0
 * 
 * API Documentation: https://nvd.nist.gov/developers/vulnerabilities
 * 
 * NVD API 2.0 Response Structure:
 * {
 *   "vulnerabilities": [
 *     {
 *       "cve": {
 *         "id": "CVE-2024-1234",
 *         "published": "2024-01-01T00:00:00",
 *         "lastModified": "2024-01-02T00:00:00",
 *         "descriptions": [{"lang": "en", "value": "..."}],
 *         "metrics": {
 *           "cvssV31": [{"baseScore": 7.5, "baseSeverity": "HIGH"}],
 *           "cvssV30": [...],
 *           "cvssV2": [...]
 *         },
 *         "references": [{"url": "https://..."}]
 *       }
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/config.php';

class CVEFetcher {
    
    /**
     * Fetch CVEs from NVD API 2.0
     * 
     * @param string $keyword (software name)
     * @param string $startIndex (optional, for pagination)
     * @return array
     */
    public static function searchCVEs($keyword, $startIndex = 0) {
        // Build query
        $query = urlencode($keyword);
        $url = NVD_API . '?keywordSearch=' . $query;
        
        // Add pagination
        $url .= '&startIndex=' . intval($startIndex);
        $url .= '&resultsPerPage=' . intval(MAX_CVE_RESULTS);
        
        // Add API key if available
        if (NVD_API_KEY) {
            $url .= '&apiKey=' . NVD_API_KEY;
        }
        
        $response = self::makeRequest($url);
        
        if (!$response || !isset($response['vulnerabilities'])) {
            return [];
        }
        
        return self::parseNVDResponse($response['vulnerabilities']);
    }
    
    /**
     * Parse NVD API 2.0 response
     * 
     * Handles CVSS v3.1, v3.0, and v2.0 formats
     * 
     * @param array $vulnerabilities
     * @return array
     */
    private static function parseNVDResponse($vulnerabilities) {
        $cves = [];
        
        foreach ($vulnerabilities as $vuln) {
            $cve = $vuln['cve'] ?? [];
            $cveId = $cve['id'] ?? 'UNKNOWN';
            
            // Get descriptions
            $descriptions = $cve['descriptions'] ?? [];
            $description = 'No description available';
            foreach ($descriptions as $desc) {
                if ($desc['lang'] === 'en') {
                    $description = $desc['value'] ?? $description;
                    break;
                }
            }
            
            // Get CVSS scores and severity
            $cvssScore = null;
            $severity = 'UNKNOWN';
            $metrics = $cve['metrics'] ?? [];
            
            // Priority: CVSS v3.1 > v3.0 > v2.0
            if (isset($metrics['cvssV31']) && is_array($metrics['cvssV31'])) {
                foreach ($metrics['cvssV31'] as $cvssData) {
                    if (isset($cvssData['baseScore'])) {
                        $cvssScore = $cvssData['baseScore'];
                        $severity = $cvssData['baseSeverity'] ?? 'UNKNOWN';
                        break;
                    }
                }
            } elseif (isset($metrics['cvssV30']) && is_array($metrics['cvssV30'])) {
                foreach ($metrics['cvssV30'] as $cvssData) {
                    if (isset($cvssData['baseScore'])) {
                        $cvssScore = $cvssData['baseScore'];
                        $severity = $cvssData['baseSeverity'] ?? 'UNKNOWN';
                        break;
                    }
                }
            } elseif (isset($metrics['cvssV2']) && is_array($metrics['cvssV2'])) {
                foreach ($metrics['cvssV2'] as $cvssData) {
                    if (isset($cvssData['baseScore'])) {
                        $cvssScore = $cvssData['baseScore'];
                        $severity = $cvssData['baseSeverity'] ?? 'UNKNOWN';
                        break;
                    }
                }
            }
            
            $cves[] = [
                'id' => $cveId,
                'description' => $description,
                'publishedDate' => $cve['published'] ?? null,
                'lastModifiedDate' => $cve['lastModified'] ?? null,
                'cvssScore' => $cvssScore,
                'severity' => $severity,
                'url' => 'https://nvd.nist.gov/vuln/detail/' . $cveId,
                'references' => self::extractReferences($cve)
            ];
        }
        
        return $cves;
    }
    
    /**
     * Extract references from CVE data
     * 
     * @param array $cve
     * @return array
     */
    private static function extractReferences($cve) {
        $references = [];
        
        $refs = $cve['references'] ?? [];
        foreach ($refs as $ref) {
            if (isset($ref['url'])) {
                $references[] = $ref['url'];
            }
        }
        
        return array_slice($references, 0, 3); // Limit to 3 references
    }
    
    /**
     * Get detailed information about a specific CVE
     * 
     * @param string $cveId (e.g., "CVE-2024-1234")
     * @return array|null
     */
    public static function getCVEDetails($cveId) {
        $url = NVD_API . '?cveId=' . urlencode($cveId);
        
        if (NVD_API_KEY) {
            $url .= '&apiKey=' . NVD_API_KEY;
        }
        
        $response = self::makeRequest($url);
        
        if (!$response || !isset($response['vulnerabilities'][0])) {
            return null;
        }
        
        $vuln = $response['vulnerabilities'][0];
        $parsed = self::parseNVDResponse([$vuln]);
        
        return $parsed[0] ?? null;
    }
    
    /**
     * Make HTTP request to NVD API
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
        
        // NVD API may return 200 with an error message
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
