<?php
/**
 * API Test Script
 * 
 * Testet die echten API-Responses von endoflife.date und NVD
 * und zeigt die Struktur an.
 * 
 * Aufruf: php test_apis.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CheckYourVersion - API Test Script ===\n\n";

// Test 1: endoflife.date - Alle Produkte
echo "1. Testing endoflife.date - GET /api/v1/products\n";
echo "Endpoint: https://endoflife.date/api/v1/products\n";
echo "---\n";

$url = 'https://endoflife.date/api/v1/products';
$response = makeRequest($url);
if ($response) {
    echo "Status: SUCCESS\n";
    echo "Response Structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Status: FAILED\n";
}

echo "\n\n";

// Test 2: endoflife.date - Spezifisches Produkt (Windows 10)
echo "2. Testing endoflife.date - GET /api/v1/products/windows-10\n";
echo "Endpoint: https://endoflife.date/api/v1/products/windows-10\n";
echo "---\n";

$url = 'https://endoflife.date/api/v1/products/windows-10';
$response = makeRequest($url);
if ($response) {
    echo "Status: SUCCESS\n";
    echo "Response Structure (first 100 lines):\n";
    $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $lines = array_slice(explode("\n", $json), 0, 100);
    echo implode("\n", $lines) . "\n";
    
    // Zeige die Struktur eines Cycles
    if (isset($response['cycles']) && count($response['cycles']) > 0) {
        echo "\n\nFirst Cycle Structure:\n";
        echo json_encode($response['cycles'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
} else {
    echo "Status: FAILED\n";
}

echo "\n\n";

// Test 3: NVD API - CVE Suche (mit keywordSearch)
echo "3. Testing NVD API - GET /rest/json/cves/2.0?keywordSearch=Windows\n";
echo "Endpoint: https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=Windows&resultsPerPage=1\n";
echo "---\n";

$url = 'https://services.nvd.nist.gov/rest/json/cves/2.0?keywordSearch=Windows&resultsPerPage=1';
$response = makeRequest($url);
if ($response) {
    echo "Status: SUCCESS\n";
    echo "Response Structure:\n";
    $json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $lines = array_slice(explode("\n", $json), 0, 100);
    echo implode("\n", $lines) . "\n";
    
    if (isset($response['vulnerabilities']) && count($response['vulnerabilities']) > 0) {
        echo "\n\nFirst Vulnerability Full Structure:\n";
        echo json_encode($response['vulnerabilities'][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
} else {
    echo "Status: FAILED\n";
}

echo "\n\n";

// Test 4: NVD API - Spezifische CVE
echo "4. Testing NVD API - GET /rest/json/cves/2.0?cveId=CVE-2024-1234\n";
echo "Endpoint: https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=CVE-2024-1234\n";
echo "---\n";

$url = 'https://services.nvd.nist.gov/rest/json/cves/2.0?cveId=CVE-2024-1234';
$response = makeRequest($url);
if ($response) {
    echo "Status: SUCCESS\n";
    echo "Response Structure:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "Status: FAILED\n";
}

echo "\n\n=== Test Complete ===\n";

/**
 * Make HTTP request
 */
function makeRequest($url) {
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: CheckYourVersion-Test/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "CURL Error: $curlError\n";
        return null;
    }
    
    if ($httpCode !== 200) {
        echo "HTTP Error: $httpCode\n";
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "JSON Decode Error\n";
        return null;
    }
    
    return $data;
}
?>
