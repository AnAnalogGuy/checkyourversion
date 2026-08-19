<?php
/**
 * Version Checker - Integration with endoflife.date API
 */

require_once __DIR__ . '/config.php';

class VersionChecker {
    
    /**
     * Search for software on endoflife.date
     * 
     * @param string $softwareName
     * @return array|null
     */
    public static function searchSoftware($softwareName) {
        $url = ENDOFLIFE_API;
        
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
        
        // Search for matching software
        if (!is_array($data)) {
            return null;
        }
        
        foreach ($data as $software) {
            if (stripos($software, $softwareName) !== false) {
                return $software;
            }
        }
        
        return null;
    }
    
    /**
     * Get version information for a specific software
     * 
     * @param string $softwareName (e.g., "windows-10")
     * @return array|null
     */
    public static function getVersionInfo($softwareName) {
        $url = ENDOFLIFE_API . '/' . urlencode($softwareName) . '.json';
        
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
        
        return json_decode($response, true);
    }
    
    /**
     * Check if a version is current/up-to-date
     * 
     * @param string $softwareName
     * @param string $userVersion
     * @return array with status and details
     */
    public static function checkVersion($softwareName, $userVersion) {
        $versionData = self::getVersionInfo($softwareName);
        
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
        
        // Get latest version
        $latestVersion = $versionData['latest'] ?? null;
        $cycles = $versionData['cycles'] ?? [];
        
        // Find the cycle that matches the user's version
        $userCycle = null;
        foreach ($cycles as $cycle => $cycleData) {
            if ($cycle === $userVersion || (isset($cycleData['version']) && $cycleData['version'] === $userVersion)) {
                $userCycle = $cycleData;
                break;
            }
        }
        
        $eolDate = $userCycle['eol'] ?? null;
        $isEOL = $eolDate ? (strtotime($eolDate) < time()) : false;
        $isUpToDate = version_compare($userVersion, $latestVersion, '>=');
        
        return [
            'status' => 'success',
            'currentVersion' => $latestVersion,
            'userVersion' => $userVersion,
            'isUpToDate' => $isUpToDate,
            'eolDate' => $eolDate,
            'isEOL' => $isEOL,
            'releaseDate' => $userCycle['releaseDate'] ?? null,
            'supportStatus' => $isEOL ? 'End of Life' : 'Supported'
        ];
    }
}
?>