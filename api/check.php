<?php
/**
 * CheckYourVersion API Endpoint
 * 
 * POST /api/check.php
 * Parameters:
 * - software: Software name (string)
 * - vendor: Vendor/Manufacturer (string)
 * - version: Version number (string)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/version_checker.php';
require_once __DIR__ . '/../includes/cve_fetcher.php';
require_once __DIR__ . '/../includes/recommendations.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

$software = $input['software'] ?? null;
$vendor = $input['vendor'] ?? null;
$version = $input['version'] ?? null;

// Validate input
if (!$software || !$version) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: software and version']);
    exit();
}

try {
    // Step 1: Get version information
    $versionData = VersionChecker::checkVersion($software, $version);
    
    // Step 2: Search for CVEs
    $searchKeyword = $vendor ? "$vendor $software" : $software;
    $cves = CVEFetcher::searchCVEsNVD($searchKeyword);
    
    // Limit CVE results
    $cves = array_slice($cves, 0, MAX_CVE_RESULTS);
    
    // Step 3: Generate recommendations
    $recommendation = Recommendations::generateRecommendation($versionData, $cves);
    
    // Build response
    $response = [
        'status' => 'success',
        'timestamp' => date('Y-m-d H:i:s'),
        'input' => [
            'software' => $software,
            'vendor' => $vendor,
            'version' => $version
        ],
        'versionInfo' => $versionData,
        'vulnerabilities' => [
            'total' => count($cves),
            'cves' => $cves
        ],
        'recommendation' => $recommendation
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>