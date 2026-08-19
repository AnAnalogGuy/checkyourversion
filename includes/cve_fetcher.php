<?php
/**
 * CVE Fetcher - Integration with NVD and CVE.org APIs
 */

require_once __DIR__ . '/config.php';

class CVEFetcher {
    
    /**
     * Fetch CVEs from NVD API
     * 
     * @param string $keyword (software name)
     * @return array
     */
    public static function searchCVEsNVD($keyword) {
        $url = NVD_API . '?keywordSearch=' . urlencode($keyword);
        
        if (NVD_API_KEY) {
            $url .= '&apiKey=' . NVD_API_KEY;
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'CheckYourVersion/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['vulnerabilities' => [], 'error' => 'Failed to fetch from NVD'];
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['vulnerabilities'])) {
            return ['vulnerabilities' => []];
        }
        
        return self::parseNVDResponse($data['vulnerabilities']);
    }
    
    /**
     * Parse NVD API response
     * 
     * @param array $vulnerabilities
     * @return array
     */
    private static function parseNVDResponse($vulnerabilities) {
        $cves = [];
        
        foreach ($vulnerabilities as $vuln) {
            $cve = $vuln['cve'] ?? [];
            $metrics = $cve['metrics'] ?? [];
            
            // Get CVSS score and severity
            $cvssScore = null;
            $severity = 'UNKNOWN';
            
            if (isset($metrics['cvssV31'])) {
                $cvssScore = $metrics['cvssV31']['baseScore'] ?? null;
                $severity = $metrics['cvssV31']['baseSeverity'] ?? 'UNKNOWN';
            } elseif (isset($metrics['cvssV30'])) {
                $cvssScore = $metrics['cvssV30']['baseScore'] ?? null;
                $severity = $metrics['cvssV30']['baseSeverity'] ?? 'UNKNOWN';
            }
            
            $cves[] = [
                'id' => $cve['id'] ?? 'UNKNOWN',
                'description' => $cve['descriptions'][0]['value'] ?? 'No description',
                'publishedDate' => $cve['published'] ?? null,
                'cvssScore' => $cvssScore,
                'severity' => $severity,
                'url' => 'https://nvd.nist.gov/vuln/detail/' . ($cve['id'] ?? '')
            ];
        }
        
        return $cves;
    }
    
    /**
     * Fetch CVEs from CVE.org API (alternative)
     * 
     * @param string $keyword
     * @return array
     */
    public static function searchCVEsORG($keyword) {
        // CVE.org API endpoint for searching
        $url = 'https://cveawg.mitre.org/api/cve';
        
        // Note: CVE.org has rate limiting, adjust as needed
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['error' => 'Failed to fetch from CVE.org'];
        }
        
        $data = json_decode($response, true);
        
        return $data ?: [];
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
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        return $data['vulnerabilities'][0] ?? null;
    }
}
?>