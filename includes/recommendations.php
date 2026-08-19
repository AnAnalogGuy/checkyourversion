<?php
/**
 * Recommendations Engine - Generates action recommendations based on version and CVE data
 */

require_once __DIR__ . '/config.php';

class Recommendations {
    
    /**
     * Generate recommendation based on version status and CVEs
     * 
     * @param array $versionData
     * @param array $cves
     * @return array
     */
    public static function generateRecommendation($versionData, $cves) {
        $urgency = 'LOW';
        $actions = [];
        $riskScore = 0;
        
        // Calculate risk score based on version status
        if ($versionData['status'] === 'error') {
            return [
                'urgency' => 'MEDIUM',
                'actions' => ['Unable to determine version status. Please verify software name and version.'],
                'riskScore' => 50
            ];
        }
        
        // Check for CVEs and their severity
        $criticalCVEs = 0;
        $highCVEs = 0;
        $mediumCVEs = 0;
        
        foreach ($cves as $cve) {
            switch ($cve['severity'] ?? 'UNKNOWN') {
                case 'CRITICAL':
                    $criticalCVEs++;
                    $riskScore += 40;
                    break;
                case 'HIGH':
                    $highCVEs++;
                    $riskScore += 20;
                    break;
                case 'MEDIUM':
                    $mediumCVEs++;
                    $riskScore += 10;
                    break;
                default:
                    $riskScore += 5;
            }
        }
        
        // Check EOL status
        if ($versionData['isEOL']) {
            $riskScore += 30;
            $actions[] = '🚨 CRITICAL: Software has reached End of Life (' . $versionData['eolDate'] . '). No security updates are available.';
        }
        
        // Check if version is up to date
        if (!$versionData['isUpToDate']) {
            $riskScore += 15;
            $actions[] = '⚠️  Version is outdated. Current version: ' . $versionData['currentVersion'];
        }
        
        // Critical CVEs detected
        if ($criticalCVEs > 0) {
            $urgency = 'CRITICAL';
            $actions[] = '🔴 CRITICAL: ' . $criticalCVEs . ' critical vulnerability/ies found. Immediate action required.';
        }
        
        // High severity CVEs
        if ($highCVEs > 0 && $urgency !== 'CRITICAL') {
            $urgency = 'HIGH';
            $actions[] = '🟠 HIGH: ' . $highCVEs . ' high-severity vulnerability/ies detected. Update urgently.';
        }
        
        // Medium severity CVEs
        if ($mediumCVEs > 0 && $urgency === 'LOW') {
            $urgency = 'MEDIUM';
            $actions[] = '🟡 MEDIUM: ' . $mediumCVEs . ' medium-severity vulnerability/ies found. Plan an update.';
        }
        
        // No issues found
        if (empty($cves) && !$versionData['isEOL'] && $versionData['isUpToDate']) {
            $urgency = 'LOW';
            $actions[] = '✅ No known vulnerabilities found and software is up to date.';
            $riskScore = 0;
        }
        
        // Generate action recommendations
        if ($versionData['isEOL']) {
            $actions[] = '📋 ACTION: Consider migrating to a currently supported version or alternative software.';
        } elseif (!$versionData['isUpToDate'] || $criticalCVEs > 0 || $highCVEs > 0) {
            $actions[] = '📋 ACTION: Update to version ' . $versionData['currentVersion'] . ' as soon as possible.';
        }
        
        // Cap risk score at 100
        $riskScore = min($riskScore, 100);
        
        return [
            'urgency' => $urgency,
            'riskScore' => $riskScore,
            'actions' => $actions,
            'summary' => self::generateSummary($versionData, $criticalCVEs, $highCVEs, $mediumCVEs)
        ];
    }
    
    /**
     * Generate a summary text
     * 
     * @param array $versionData
     * @param int $critical
     * @param int $high
     * @param int $medium
     * @return string
     */
    private static function generateSummary($versionData, $critical, $high, $medium) {
        $summary = '';
        
        if ($versionData['isEOL']) {
            $summary .= 'End-of-Life Software • ';
        }
        
        if (!$versionData['isUpToDate']) {
            $summary .= 'Outdated Version • ';
        }
        
        if ($critical > 0) {
            $summary .= $critical . ' Critical CVEs • ';
        }
        
        if ($high > 0) {
            $summary .= $high . ' High CVEs • ';
        }
        
        if ($medium > 0) {
            $summary .= $medium . ' Medium CVEs';
        }
        
        return trim($summary, ' •');
    }
    
    /**
     * Map urgency level to CSS class
     * 
     * @param string $urgency
     * @return string
     */
    public static function getUrgencyClass($urgency) {
        switch ($urgency) {
            case 'CRITICAL':
                return 'urgency-critical';
            case 'HIGH':
                return 'urgency-high';
            case 'MEDIUM':
                return 'urgency-medium';
            case 'LOW':
                return 'urgency-low';
            default:
                return 'urgency-unknown';
        }
    }
}
?>