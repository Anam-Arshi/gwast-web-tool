<?php
session_start();

// Clear existing session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]);
}
session_destroy();

require_once '../../connect.php';
include('../../header.php'); // Your existing header
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Enhanced GWAST theme with gray colors */
:root {
    --gwast-brown: #8B7355;
    --gwast-light: #F5F2E8;
    --gwast-beige: #E8DCC0;
    --gwast-dark: #5D4E37;
    --gwast-accent: #A0906B;
    --gwast-gray: #6C757D;
    --gwast-light-gray: #F8F9FA;
    --gwast-dark-gray: #495057;
}

.disease-analyzer-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 20px;
}

.analyzer-header {
    background: linear-gradient(135deg, var(--gwast-light) 0%, var(--gwast-beige) 100%);
    border: 1px solid var(--gwast-brown);
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(139, 115, 85, 0.1);
}

.analyzer-header h2 {
    color: var(--gwast-dark);
    font-size: 2.2rem;
    margin-bottom: 10px;
    font-weight: 600;
}

.analyzer-subtitle {
    color: var(--gwast-brown);
    font-size: 1.1rem;
    font-weight: 400;
}

.analyzer-form {
    background: white;
    border: 1px solid var(--gwast-brown);
    border-radius: 8px;
    padding: 40px;
    box-shadow: 0 2px 10px rgba(139, 115, 85, 0.1);
}

.form-group {
    margin-bottom: 25px;
}

.form-label {
    display: block;
    color: var(--gwast-dark);
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.form-note {
    color: var(--gwast-brown);
    font-size: 0.9rem;
    font-weight: normal;
    margin-left: 10px;
}

/* Fixed selection info styling */
/* compact beige/gray info bar */
.selection-info {
    background: var(--gwast-light-gray);   /* light gray */
    border-left: 4px solid var(--gwast-brown);  /* brown accent line */
    padding: 10px 15px;
    margin-top: 10px;
    border-radius: 0 4px 4px 0;
    font-size: .9rem;
    color: var(--gwast-dark-gray);
    display: none;          /* shown via JS */
}

/* no special colours needed any more */
.selection-info.success,
.selection-info.warning { all: unset; }


/* Fixed Select2 styling - prevents width issues on load */
.disease-select {
    width: 100% !important;
}

.select2-container {
    width: 100% !important;
    display: block !important;
}

.select2-container--default .select2-selection--multiple {
    border: 2px solid var(--gwast-brown);
    border-radius: 6px;
    min-height: 120px;
    padding: 8px;
    background: white;
    font-family: inherit;
    width: 100%;
    box-sizing: border-box;
}

.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    padding: 0;
    margin: 0;
    width: 100%;
    box-sizing: border-box;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: var(--gwast-brown);
    border: 1px solid var(--gwast-dark);
    border-radius: 4px;
    color: white;
    padding: 6px 10px;
    margin: 3px 3px 0 0;
    font-size: 14px;
    line-height: 1.4;
    display: inline-flex;
    align-items: center;
    max-width: calc(100% - 10px);
    word-break: break-word;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__display {
    cursor: default;
    padding-left: 2px;
    padding-right: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    background: none;
    border: none;
    border-right: 1px solid rgba(255, 255, 255, 0.3);
    border-top-left-radius: 4px;
    border-bottom-left-radius: 4px;
    color: white;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    padding: 0 6px;
    margin-right: 6px;
    margin-left: -2px;
    display: inline-block;
    line-height: 1;
    transition: background 0.2s ease;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.select2-container--default .select2-selection--multiple .select2-search--inline {
    float: left;
    margin: 3px 0 0 0;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    background: transparent;
    border: none;
    outline: none;
    box-shadow: none;
    color: var(--gwast-dark);
    font-size: 14px;
    padding: 4px 6px;
    margin: 0;
    min-height: auto;
    line-height: 1.4;
    width: auto !important;
    min-width: 100px;
}

.select2-dropdown {
    border: 2px solid var(--gwast-brown);
    border-radius: 6px;
    background: white;
    z-index: 9999;
}

.select2-container--default .select2-results > .select2-results__options {
    max-height: 300px;
    overflow-y: auto;
}

.select2-container--default .select2-results__option {
    padding: 8px 12px;
    color: var(--gwast-dark);
    background: white;
    cursor: pointer;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--gwast-brown) !important;
    color: white !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: var(--gwast-beige);
    color: var(--gwast-dark);
}

.select2-container--default .select2-selection--multiple .select2-selection__placeholder {
    color: var(--gwast-brown);
    margin-top: 5px;
    float: left;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: var(--gwast-dark);
    outline: none;
    box-shadow: 0 0 5px rgba(139, 115, 85, 0.3);
}

/* P-value section */
.p-value-section {
    background: var(--gwast-light);
    padding: 20px;
    border-radius: 6px;
    border: 1px solid var(--gwast-beige);
}

.p-value-input {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1rem;
    color: var(--gwast-dark);
    margin-top: 10px;
    flex-wrap: wrap;
}

.p-input {
    width: 60px;
    padding: 8px;
    border: 1px solid var(--gwast-brown);
    border-radius: 4px;
    text-align: center;
    font-size: 1rem;
    background: white;
    transition: border-color 0.2s ease;
}

.p-input:focus {
    border-color: var(--gwast-dark);
    outline: none;
    box-shadow: 0 0 3px rgba(139, 115, 85, 0.3);
}

/* Enhanced button styling with gray colors */
.form-actions {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--gwast-beige);
}

.gwast-btn {
    padding: 12px 25px;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin: 0 10px;
    transition: all 0.3s ease;
    min-width: 120px;
    position: relative;
    overflow: hidden;
}

.gwast-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.gwast-btn-primary {
    background: linear-gradient(135deg, var(--gwast-brown), var(--gwast-dark));
    color: white;
    border: 1px solid var(--gwast-dark);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.gwast-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, var(--gwast-dark), var(--gwast-brown));
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.gwast-btn-secondary {
    background: linear-gradient(135deg, var(--gwast-gray), var(--gwast-dark-gray));
    color: white;
    border: 1px solid var(--gwast-dark-gray);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.gwast-btn-secondary:hover {
    background: linear-gradient(135deg, var(--gwast-dark-gray), var(--gwast-gray));
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

/* Loading states */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(93, 78, 55, 0.8);
    display: none;
    z-index: 99999;
}

.loading-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    border: 2px solid var(--gwast-brown);
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--gwast-beige);
    border-top: 3px solid var(--gwast-brown);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    color: var(--gwast-dark);
    font-weight: 600;
}

/* Enhanced alert styling to match theme better */
.gwast-alert {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    z-index: 100000;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 15px rgba(139, 115, 85, 0.2);
    animation: slideDown 0.3s ease-out;
    max-width: 90%;
    word-wrap: break-word;
    border: 2px solid;
    backdrop-filter: blur(2px);
}

.gwast-alert-error {
    background: linear-gradient(135deg, #F8D7DA, #F5C6CB);
    border-color: var(--gwast-brown);
    color: var(--gwast-dark);
}

.gwast-alert-warning {
    background: linear-gradient(135deg, #FFF3CD, #FFEAA7);
    border-color: var(--gwast-accent);
    color: var(--gwast-dark);
}

.gwast-alert-success {
    background: linear-gradient(135deg, #D4EDDA, #C3E6CB);
    border-color: var(--gwast-brown);
    color: var(--gwast-dark);
}

.alert-close {
    background: rgba(139, 115, 85, 0.1);
    border: 1px solid rgba(139, 115, 85, 0.3);
    color: var(--gwast-dark);
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 50%;
    width: 26px;
    height: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-weight: bold;
}

.alert-close:hover {
    background: rgba(139, 115, 85, 0.2);
    border-color: var(--gwast-brown);
}

@keyframes slideDown {
    from { opacity: 0; transform: translate(-50%, -20px); }
    to { opacity: 1; transform: translate(-50%, 0); }
}

/* Responsive design */
@media (max-width: 768px) {
    .disease-analyzer-container {
        padding: 0 15px;
    }
    
    .analyzer-form {
        padding: 25px;
    }
    
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        align-items: center;
    }
    
    .gwast-btn {
        width: 100%;
        max-width: 250px;
        margin: 5px 0;
    }
    
    .p-value-input {
        justify-content: center;
    }
    
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        font-size: 13px;
        padding: 4px 8px;
        margin: 2px 2px 0 0;
    }
}

@media (max-width: 480px) {
    .analyzer-header h2 {
        font-size: 1.8rem;
    }
    
    .analyzer-form {
        padding: 20px;
    }
}

/* Prevent FOUC (Flash of Unstyled Content) */
.select2-hidden-accessible {
    border: 0 !important;
    clip: rect(0 0 0 0) !important;
    height: 1px !important;
    margin: -1px !important;
    overflow: hidden !important;
    padding: 0 !important;
    position: absolute !important;
    width: 1px !important;
}
</style>

<!-- Prevent width flash by hiding initially -->
<style>
.disease-select {
    visibility: hidden;
}
.select2-loaded .disease-select {
    visibility: visible;
}
</style>

<div class="disease-analyzer-container">
    <div class="analyzer-header">
        <h2>Disease Analyzer</h2>
        <p class="analyzer-subtitle">Select and filter disease-associated SNPs for downstream analysis</p>
    </div>

    <div class="analyzer-form">
        <form id="diseaseForm" action="disease-analyzer-results.php" method="post">
            <div class="form-group">
                <label for="diseases" class="form-label">
                    Diseases/Traits/Phenotypes:
                    <span class="form-note">(Maximum 6 selections allowed)</span>
                </label>
                
                <select id="diseases" name="dis_grr[]" multiple class="disease-select">
                    <?php
                    try {
                        $stmt = $conn->prepare("SELECT DISTINCT disease_merge FROM snp_table WHERE disease_merge IS NOT NULL AND disease_merge != '' ORDER BY disease_merge");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()) {
                            $disease = htmlspecialchars($row['disease_merge']);
                            echo "<option value=\"{$disease}\">{$disease}</option>";
                        }
                    } catch (Exception $e) {
                        echo "<option disabled>Error loading diseases</option>";
                    }
                    ?>
                </select>
                
                <div id="selectionInfo" class="selection-info">
                    <strong>Selected:</strong> <span id="selectedCount">0</span> of 6 diseases
                </div>
            </div>

            <div class="form-group">
                <div class="p-value-section">
                    <label class="form-label">P-value cutoff:</label>
                    <div class="p-value-input">
                        <input type="number" name="p_value1" id="p_value1" value="5" min="1" max="9" class="p-input" required> 
                        × 10<sup>-<input type="number" name="p_value2" id="p_value2" value="8" min="1" max="50" class="p-input" required></sup>
                    </div>
                </div>
            </div>

            <input type="hidden" name="dataset" value="disease">

            <div class="form-actions">
                <button type="submit" class="gwast-btn gwast-btn-primary" id="submitBtn">
                    <span class="btn-text">Submit Analysis</span>
                    <span class="loading-spinner" style="display: none;">Processing...</span>
                </button>
                <button type="button" id="resetBtn" class="gwast-btn gwast-btn-secondary">Reset Form</button>
            </div>
        </form>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <p class="loading-text">Processing your request...</p>
        <p style="font-size: 0.9rem; color: var(--gwast-brown); margin-top: 10px;">This may take a few moments</p>
    </div>
</div>

<script>
$(document).ready(function() {
    let isInitialized = false;
    
    // Initialize Select2 immediately to prevent width jumping
    function initializeSelect2() {
        if (isInitialized) return;
        
        $('#diseases').select2({
            placeholder: 'Search and select diseases or traits...',
            allowClear: true,
            maximumSelectionLength: 6,
            width: '100%',
            closeOnSelect: false,
            tags: false,
            tokenSeparators: [],
            language: {
                maximumSelected: function(e) {
                    return "You can only select " + e.maximum + " diseases at a time.";
                },
                noResults: function() {
                    return "No diseases found. Try different search terms.";
                },
                searching: function() {
                    return "Searching diseases...";
                }
            },
            escapeMarkup: function(markup) {
                return markup;
            },
            templateResult: function(data) {
                if (data.loading) {
                    return data.text;
                }
                return $('<div>').text(data.text).html();
            },
            templateSelection: function(data) {
                return $('<div>').text(data.text).html();
            }
        });
        
        isInitialized = true;
        updateSelectionInfo();
    }
    
    // Initialize immediately
    initializeSelect2();
    
    // Show select2 after initialization to prevent width flash
    setTimeout(function() {
        $('body').addClass('select2-loaded');
    }, 100);

    // Fixed selection info update
    function updateSelectionInfo() {
    const count = ($('#diseases').val() || []).length;
    const info  = $('#selectionInfo');
    $('#selectedCount').text(count);

    if (count === 0) {
        info.hide();
        $('#submitBtn').prop('disabled', true);
    } else {
        info.show();
        $('#submitBtn').prop('disabled', false);
    }
}

    
    // Enhanced form validation and submission
    $('#diseaseForm').on('submit', function(e) {
        e.preventDefault();
        
        const selectedDiseases = $('#diseases').val();
        const pValue1 = parseInt($('#p_value1').val());
        const pValue2 = parseInt($('#p_value2').val());

        // Comprehensive validation
        if (!selectedDiseases || selectedDiseases.length === 0) {
            showAlert('Please select at least one disease or trait for analysis.', 'error');
            return false;
        }

        if (selectedDiseases.length > 6) {
            showAlert('Maximum 6 diseases can be selected at once.', 'error');
            return false;
        }

        if (!pValue1 || !pValue2 || isNaN(pValue1) || isNaN(pValue2)) {
            showAlert('Please enter valid numerical p-values.', 'error');
            return false;
        }

        if (pValue1 < 1 || pValue1 > 9) {
            showAlert('P-value coefficient must be between 1 and 9.', 'error');
            $('#p_value1').focus();
            return false;
        }

        if (pValue2 < 1 || pValue2 > 50) {
            showAlert('P-value exponent must be between 1 and 50.', 'error');
            $('#p_value2').focus();
            return false;
        }

        // Show processing message and submit immediately
        // showAlert(`Processing analysis for ${selectedDiseases.length} disease(s)...`, 'success');
        showLoading();
        
        // Submit directly without delay
        setTimeout(() => {
            this.submit();
        }, 500);
    });

    // Enhanced reset functionality
    $('#resetBtn').on('click', function() {
        $(this).prop('disabled', true);
        
        $('#diseases').val(null).trigger('change');
        $('#p_value1').val('5');
        $('#p_value2').val('8');
        
        showAlert('Form reset successfully.', 'success');
        
        setTimeout(() => {
            $(this).prop('disabled', false);
        }, 1000);
    });

    // Simplified selection change handlers - removed unnecessary alerts
    $('#diseases').on('select2:select select2:unselect', function(e) {
        updateSelectionInfo();
        
        const selectedCount = $(this).val() ? $(this).val().length : 0;
        
        // Only show alert when reaching maximum limit
        if (e.type === 'select2:select' && selectedCount === 6) {
            showAlert('Maximum selection limit reached.', 'warning');
        }
    });

    // P-value input validation
    $('#p_value1, #p_value2').on('input change', function() {
        const value = parseInt($(this).val());
        const isFirst = $(this).attr('id') === 'p_value1';
        const min = isFirst ? 1 : 1;
        const max = isFirst ? 9 : 50;
        
        if (value < min || value > max) {
            $(this).css('border-color', '#DC3545');
            $(this).css('background', '#FFF5F5');
        } else {
            $(this).css('border-color', 'var(--gwast-brown)');
            $(this).css('background', 'white');
        }
    });

    // Initialize selection info
    updateSelectionInfo();
});

function showLoading() {
    $('#loadingOverlay').fadeIn(300);
    $('.btn-text').hide();
    $('.loading-spinner').show();
    $('#submitBtn').prop('disabled', true);
}

function showAlert(message, type = 'error') {
    const alertClass = `gwast-alert-${type}`;
    const alertHtml = `
        <div class="gwast-alert ${alertClass}">
            <span>${message}</span>
            <button class="alert-close" aria-label="Close">&times;</button>
        </div>
    `;
    
    $('.gwast-alert').remove();
    $('body').prepend(alertHtml);
    
    // Auto-remove based on type
    const autoRemoveTime = type === 'error' ? 6000 : (type === 'warning' ? 4000 : 2500);
    setTimeout(() => {
        $('.gwast-alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, autoRemoveTime);
    
    // Manual close
    $('.alert-close').on('click', function() {
        $(this).parent().fadeOut(300, function() {
            $(this).remove();
        });
    });
}
</script>

<?php include('../../footer.php'); // Your existing footer ?>
