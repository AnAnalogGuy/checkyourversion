<?php
/**
 * Configuration file for CheckYourVersion
 */

// API Endpoints
define('ENDOFLIFE_API', 'https://endoflife.date/api/v1');
define('NVD_API', 'https://services.nvd.nist.gov/rest/json/cves/2.0');

// CVE.org alternative (if needed)
define('CVE_ORG_API', 'https://www.cve.org/api/');

// API Keys (if needed)
define('NVD_API_KEY', ''); // Add your NVD API key if available

// Timeout for API calls (in seconds)
define('API_TIMEOUT', 10);

// Max CVE results to display
define('MAX_CVE_RESULTS', 20);

// Severity levels
define('SEVERITY_CRITICAL', 'CRITICAL');
define('SEVERITY_HIGH', 'HIGH');
define('SEVERITY_MEDIUM', 'MEDIUM');
define('SEVERITY_LOW', 'LOW');

// Response codes
define('RESPONSE_SUCCESS', 200);
define('RESPONSE_ERROR', 500);
define('RESPONSE_NOT_FOUND', 404);

// Log directory
define('LOG_DIR', __DIR__ . '/../logs/');

// Enable error logging
define('DEBUG_MODE', true);
?>