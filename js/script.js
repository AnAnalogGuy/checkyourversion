/**
 * CheckYourVersion - Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkForm');
    const resultsContainer = document.getElementById('results');
    
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
});

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const software = document.getElementById('software').value.trim();
    const vendor = document.getElementById('vendor').value.trim();
    const version = document.getElementById('version').value.trim();
    
    // Validate input
    if (!software || !version) {
        showError('Bitte geben Sie Software-Name und Versionsnummer ein.');
        return;
    }
    
    // Show loading state
    showLoading();
    
    try {
        const response = await fetch('api/check.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                software: software,
                vendor: vendor,
                version: version
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.status === 'error') {
            showError(data.message || 'Ein Fehler ist aufgetreten.');
        } else {
            displayResults(data);
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Fehler beim Abrufen der Daten: ' + error.message);
    }
}

function displayResults(data) {
    const resultsContainer = document.getElementById('results');
    const versionInfo = data.versionInfo;
    const recommendation = data.recommendation;
    const vulnerabilities = data.vulnerabilities.cves;
    
    let html = '';
    
    // Recommendation section
    html += `
        <div class="recommendation-box ${getCSSClass('urgency', recommendation.urgency)}">
            <div class="urgency-label">🎯 Empfehlung: ${recommendation.urgency}</div>
            <div class="risk-score">
                <div class="risk-bar">
                    <div class="risk-fill" style="width: ${recommendation.riskScore}%"></div>
                </div>
                <div class="risk-percentage">${recommendation.riskScore}%</div>
            </div>
            ${recommendation.summary ? `<div style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">📊 ${recommendation.summary}</div>` : ''}
            <ul class="actions-list">
                ${recommendation.actions.map(action => `<li>${action}</li>`).join('')}
            </ul>
        </div>
    `;
    
    // Version information
    if (versionInfo.status === 'success') {
        html += `
            <div class="section">
                <h3 class="section-title">📦 Versionsinformationen</h3>
                <div class="info-grid">
                    <div class="info-card">
                        <div class="label">Ihre Version</div>
                        <div class="value">${versionInfo.userVersion}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Aktuelle Version</div>
                        <div class="value">${versionInfo.currentVersion || 'N/A'}</div>
                    </div>
                    <div class="info-card">
                        <div class="label">Status</div>
                        <div class="value">
                            <span class="status-badge ${versionInfo.isUpToDate ? 'status-up-to-date' : 'status-outdated'}">
                                ${versionInfo.isUpToDate ? '✓ Aktuell' : '⚠️ Veraltet'}
                            </span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="label">Support-Status</div>
                        <div class="value">
                            <span class="status-badge ${versionInfo.isEOL ? 'status-eol' : 'status-up-to-date'}">
                                ${versionInfo.isEOL ? '❌ End of Life' : '✓ Unterstützt'}
                            </span>
                        </div>
                    </div>
                    ${versionInfo.eolDate ? `
                        <div class="info-card">
                            <div class="label">EOL-Datum</div>
                            <div class="value">${formatDate(versionInfo.eolDate)}</div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Vulnerabilities
    html += `
        <div class="section">
            <h3 class="section-title">🔒 Sicherheitslücken</h3>
    `;
    
    if (vulnerabilities && vulnerabilities.length > 0) {
        html += `<p style="margin-bottom: 20px; color: #666;">Gefunden: <strong>${vulnerabilities.length}</strong> CVE(s)</p>`;
        html += '<ul class="cve-list">';
        
        vulnerabilities.forEach(cve => {
            html += `
                <li class="cve-item">
                    <div class="cve-header">
                        <a href="${cve.url}" target="_blank" class="cve-link">${cve.id}</a>
                        <span class="severity-badge severity-${(cve.severity || 'unknown').toLowerCase()}">
                            ${cve.severity || 'UNKNOWN'}
                        </span>
                    </div>
                    <div class="cve-description">${cve.description}</div>
                    <div class="cve-meta">
                        ${cve.cvssScore ? `<div><strong>CVSS:</strong> ${cve.cvssScore}</div>` : ''}
                        ${cve.publishedDate ? `<div><strong>Veröffentlicht:</strong> ${formatDate(cve.publishedDate)}</div>` : ''}
                    </div>
                </li>
            `;
        });
        
        html += '</ul>';
    } else {
        html += `
            <div class="no-results">
                <div class="no-results-icon">✅</div>
                <p>Keine bekannten Sicherheitslücken gefunden.</p>
            </div>
        `;
    }
    
    html += '</div>';
    
    resultsContainer.innerHTML = html;
    resultsContainer.classList.add('show');
}

function showLoading() {
    const resultsContainer = document.getElementById('results');
    resultsContainer.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Überprüfung läuft...</p>
        </div>
    `;
    resultsContainer.classList.add('show');
}

function showError(message) {
    const resultsContainer = document.getElementById('results');
    resultsContainer.innerHTML = `
        <div class="error-message">
            <strong>Fehler:</strong> ${message}
        </div>
    `;
    resultsContainer.classList.add('show');
}

function getCSSClass(type, value) {
    const value_lower = value.toLowerCase();
    return `${type}-${value_lower}`;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}