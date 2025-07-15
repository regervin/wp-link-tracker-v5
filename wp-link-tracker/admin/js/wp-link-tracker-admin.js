/**
 * Admin JavaScript for WP Link Tracker - FIXED CHART TOOLTIP DISPLAY
 */
(function($) {
    'use strict';

    // Global debug flag
    var DEBUG = true;
    
    // Store chart instances
    var chartInstances = {};
    
    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[WP Link Tracker] ' + message, data || '');
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        debugLog('Document ready - initializing WP Link Tracker Admin with FIXED TOOLTIPS');
        debugLog('jQuery version:', $.fn.jquery);
        debugLog('wpLinkTrackerAdmin object:', wpLinkTrackerAdmin);
        
        // Test basic functionality
        testBasicFunctionality();
        
        // Initialize copy functionality
        initCopyToClipboard();
        
        // Initialize dashboard if we're on the dashboard page
        if ($('#wplinktracker-clicks-chart').length) {
            debugLog('Dashboard page detected - initializing dashboard with fixed tooltips');
            initDashboard();
        } else {
            debugLog('Not on dashboard page');
        }
    });

    /**
     * Test basic functionality
     */
    function testBasicFunctionality() {
        debugLog('Testing basic functionality...');
        
        // Test if wpLinkTrackerAdmin is available
        if (typeof wpLinkTrackerAdmin === 'undefined') {
            console.error('[WP Link Tracker] wpLinkTrackerAdmin object not found!');
            return false;
        }
        
        // Test AJAX URL
        if (!wpLinkTrackerAdmin.ajaxUrl) {
            console.error('[WP Link Tracker] AJAX URL not found!');
            return false;
        }
        
        // Test nonce
        if (!wpLinkTrackerAdmin.nonce) {
            console.error('[WP Link Tracker] Nonce not found!');
            return false;
        }
        
        debugLog('Basic functionality test passed');
        return true;
    }

    /**
     * Initialize copy to clipboard functionality
     */
    function initCopyToClipboard() {
        debugLog('Initializing copy to clipboard functionality');
        
        $(document).on('click', '.copy-to-clipboard', function(e) {
            e.preventDefault();
            debugLog('Copy button clicked');
            
            var $button = $(this);
            var text = $button.attr('data-clipboard-text');
            var originalText = $button.text();
            
            if (!text) {
                debugLog('No text to copy found');
                return;
            }
            
            debugLog('Copying text:', text);
            
            // Try modern clipboard API first
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    debugLog('Text copied successfully');
                    $button.text('Copied!');
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                }).catch(function(err) {
                    debugLog('Clipboard API failed, using fallback:', err);
                    fallbackCopyTextToClipboard(text, $button, originalText);
                });
            } else {
                debugLog('Using fallback copy method');
                fallbackCopyTextToClipboard(text, $button, originalText);
            }
        });
    }

    /**
     * Fallback copy function for older browsers
     */
    function fallbackCopyTextToClipboard(text, $button, originalText) {
        debugLog('Using fallback copy method for text:', text);
        
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        textArea.style.opacity = "0";
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand('copy');
            debugLog('Fallback copy result:', successful);
            
            if (successful) {
                $button.text('Copied!');
            } else {
                $button.text('Failed');
            }
            
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        } catch (err) {
            debugLog('Fallback copy failed:', err);
            $button.text('Failed');
            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        }
        
        document.body.removeChild(textArea);
    }

    /**
     * Initialize dashboard
     */
    function initDashboard() {
        debugLog('Initializing dashboard with fixed tooltips...');
        
        // Initialize charts first
        initCharts();
        
        // Load initial data
        loadDashboardStats();
        
        // Bind all button events
        bindDashboardEvents();
        
        debugLog('Dashboard initialization complete with fixed tooltips');
    }

    /**
     * Initialize empty charts - WITH FIXED TOOLTIP CONFIGURATION
     */
    function initCharts() {
        debugLog('Initializing charts with fixed tooltips...');
        
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('[WP Link Tracker] Chart.js not loaded!');
            return;
        }
        
        var emptyData = {
            labels: [],
            datasets: [{
                label: 'Clicks',
                data: [],
                backgroundColor: 'rgba(0, 115, 170, 0.2)',
                borderColor: 'rgba(0, 115, 170, 1)',
                borderWidth: 1
            }]
        };

        var emptyPieData = {
            labels: ['No Data'],
            datasets: [{
                data: [1],
                backgroundColor: ['#e0e0e0']
            }]
        };

        try {
            // Clicks chart
            if (document.getElementById('wplinktracker-clicks-chart')) {
                debugLog('Initializing clicks over time chart...');
                
                if (chartInstances.clicksChart) {
                    chartInstances.clicksChart.destroy();
                }
                
                chartInstances.clicksChart = new Chart(document.getElementById('wplinktracker-clicks-chart'), {
                    type: 'line',
                    data: emptyData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                type: 'category'
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                });
                debugLog('Clicks chart initialized successfully');
            }

            // Device chart - FIXED TOOLTIP CONFIGURATION
            if (document.getElementById('wplinktracker-devices-chart')) {
                debugLog('Initializing devices chart with fixed tooltips...');
                
                if (chartInstances.devicesChart) {
                    chartInstances.devicesChart.destroy();
                }
                
                chartInstances.devicesChart = new Chart(document.getElementById('wplinktracker-devices-chart'), {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        debugLog('Device tooltip callback - context:', context);
                                        var label = context.label || 'Unknown';
                                        var value = context.parsed || 0;
                                        debugLog('Device tooltip - label:', label, 'value:', value);
                                        return label + ': ' + value;
                                    }
                                }
                            }
                        }
                    }
                });
                debugLog('Devices chart initialized with fixed tooltips');
            }

            // Browser chart - FIXED TOOLTIP CONFIGURATION
            if (document.getElementById('wplinktracker-browsers-chart')) {
                debugLog('Initializing browsers chart with fixed tooltips...');
                
                if (chartInstances.browsersChart) {
                    chartInstances.browsersChart.destroy();
                }
                
                chartInstances.browsersChart = new Chart(document.getElementById('wplinktracker-browsers-chart'), {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        debugLog('Browser tooltip callback - context:', context);
                                        var label = context.label || 'Unknown';
                                        var value = context.parsed || 0;
                                        debugLog('Browser tooltip - label:', label, 'value:', value);
                                        return label + ': ' + value;
                                    }
                                }
                            }
                        }
                    }
                });
                debugLog('Browsers chart initialized with fixed tooltips');
            }

            // OS chart - FIXED TOOLTIP CONFIGURATION
            if (document.getElementById('wplinktracker-os-chart')) {
                debugLog('Initializing OS chart with fixed tooltips...');
                
                if (chartInstances.osChart) {
                    chartInstances.osChart.destroy();
                }
                
                chartInstances.osChart = new Chart(document.getElementById('wplinktracker-os-chart'), {
                    type: 'pie',
                    data: emptyPieData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        debugLog('OS tooltip callback - context:', context);
                                        var label = context.label || 'Unknown';
                                        var value = context.parsed || 0;
                                        debugLog('OS tooltip - label:', label, 'value:', value);
                                        return label + ': ' + value;
                                    }
                                }
                            }
                        }
                    }
                });
                debugLog('OS chart initialized with fixed tooltips');
            }
        } catch (error) {
            console.error('[WP Link Tracker] Chart initialization failed:', error);
        }

        // Initialize empty tables
        var emptyTableHtml = '<table class="widefat striped"><tbody><tr><td colspan="3" style="text-align: center; padding: 20px; color: #666;">No data available yet. Create some tracked links to see statistics here.</td></tr></tbody></table>';
        
        $('#wplinktracker-top-links-table').html(emptyTableHtml);
        $('#wplinktracker-top-referrers-table').html(emptyTableHtml);
        
        debugLog('Charts and tables initialized with fixed tooltips');
    }

    /**
     * Bind dashboard events
     */
    function bindDashboardEvents() {
        debugLog('Binding dashboard events with validation...');
        
        // Date range selector
        $('#wplinktracker-date-range-select').off('change').on('change', function() {
            var value = $(this).val();
            debugLog('Date range changed to:', value);
            
            if (value === 'custom') {
                $('#wplinktracker-custom-date-range').show();
            } else {
                $('#wplinktracker-custom-date-range').hide();
                loadDashboardStats(value);
            }
        });

        // VALIDATE ALL DATA BUTTON - THE MAIN TRUST BUILDER
        $('#wplinktracker-validate-all-data').off('click').on('click', function() {
            debugLog('Validate all data button clicked - COMPREHENSIVE VALIDATION');
            handleValidateAllData();
        });

        // Refresh dashboard button
        $('#wplinktracker-refresh-dashboard').off('click').on('click', function() {
            debugLog('Refresh dashboard button clicked');
            handleRefreshDashboard();
        });

        // Refresh data button
        $('#wplinktracker-refresh-data').off('click').on('click', function() {
            debugLog('Refresh data button clicked');
            handleRefreshData();
        });

        // View data count button
        $('#wplinktracker-view-data-count').off('click').on('click', function() {
            debugLog('View data count button clicked');
            handleViewDataCount();
        });

        // Debug date range button
        $('#wplinktracker-debug-date-range').off('click').on('click', function() {
            debugLog('Debug date range button clicked');
            handleDebugDateRange();
        });

        // Reset stats button
        $('#wplinktracker-reset-stats').off('click').on('click', function() {
            debugLog('Reset stats button clicked');
            handleResetStats();
        });

        // Apply custom date range button
        $('#wplinktracker-apply-date-range').off('click').on('click', function() {
            debugLog('Apply custom date range button clicked');
            handleApplyCustomDateRange();
        });
        
        debugLog('All dashboard events bound successfully with validation');
    }

    /**
     * COMPREHENSIVE DATA VALIDATION - THE TRUST BUILDER
     */
    function handleValidateAllData() {
        debugLog('Starting comprehensive data validation...');
        
        if (!testBasicFunctionality()) {
            debugLog('Basic functionality test failed, aborting validation');
            return;
        }
        
        // Show loading state
        var $button = $('#wplinktracker-validate-all-data');
        var originalText = $button.html();
        $button.html('<span class="dashicons dashicons-update spin"></span> Validating...').prop('disabled', true);
        
        var selectedRange = $('#wplinktracker-date-range-select').val();
        var days = selectedRange !== 'custom' ? selectedRange : 30;
        var dateFrom = '';
        var dateTo = '';
        
        if (selectedRange === 'custom') {
            dateFrom = $('#wplinktracker-date-from').val();
            dateTo = $('#wplinktracker-date-to').val();
        }
        
        var data = {
            action: 'wp_link_tracker_validate_all_data',
            nonce: wpLinkTrackerAdmin.nonce,
            days: days
        };
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        debugLog('Sending comprehensive validation request:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                debugLog('Validation response received:', response);
                
                // Restore button
                $button.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    var validation = response.data;
                    displayValidationResults(validation);
                } else {
                    alert('Validation failed: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('[WP Link Tracker] Validation request failed:', status, error);
                $button.html(originalText).prop('disabled', false);
                alert('Validation request failed: ' + status + ' - ' + error);
            });
    }

    /**
     * Display comprehensive validation results
     */
    function displayValidationResults(validation) {
        debugLog('Displaying validation results:', validation);
        
        var message = '=== COMPREHENSIVE DATA VALIDATION REPORT ===\n\n';
        message += 'Validation Time: ' + validation.validation_timestamp + '\n';
        message += 'Overall Status: ' + validation.overall_status + '\n\n';
        
        // Data Sources
        message += '--- DATA SOURCES ---\n';
        message += 'Clicks Table: ' + (validation.data_sources.clicks_table.exists ? 'EXISTS' : 'MISSING') + 
                   ' (' + validation.data_sources.clicks_table.records + ' records)\n';
        message += 'Post Meta: ' + (validation.data_sources.post_meta.exists ? 'EXISTS' : 'MISSING') + 
                   ' (' + validation.data_sources.post_meta.records + ' records)\n';
        message += 'Posts Table: ' + (validation.data_sources.posts_table.exists ? 'EXISTS' : 'MISSING') + 
                   ' (' + validation.data_sources.posts_table.records + ' records)\n\n';
        
        // Panel Validations
        message += '--- PANEL VALIDATIONS ---\n';
        Object.keys(validation.panel_validations).forEach(function(panel) {
            var result = validation.panel_validations[panel];
            message += panel.toUpperCase() + ': ' + result.status;
            if (result.error) {
                message += ' - ERROR: ' + result.error;
            }
            if (result.warning) {
                message += ' - WARNING: ' + result.warning;
            }
            message += '\n';
        });
        
        // Device Data Analysis (THE CRITICAL ONE)
        if (validation.panel_validations.device_data) {
            var deviceValidation = validation.panel_validations.device_data;
            message += '\n--- DEVICE DATA DETAILED ANALYSIS ---\n';
            
            if (deviceValidation.data_mapping_check) {
                message += 'Device Type Contains OS Data: ' + (deviceValidation.data_mapping_check.device_type_contains_os_data ? 'YES (PROBLEM!)' : 'No') + '\n';
                message += 'Device Type Contains Device Data: ' + (deviceValidation.data_mapping_check.device_type_contains_device_data ? 'Yes' : 'NO (PROBLEM!)') + '\n';
                message += 'Suspected Data Corruption: ' + (deviceValidation.data_mapping_check.suspected_data_corruption ? 'YES (CRITICAL!)' : 'No') + '\n';
                
                if (deviceValidation.data_mapping_check.device_type_values) {
                    message += 'Device Type Values: ' + deviceValidation.data_mapping_check.device_type_values.join(', ') + '\n';
                }
            }
            
            if (deviceValidation.expected_vs_actual) {
                message += 'Expected Device Types: ' + deviceValidation.expected_vs_actual.expected_device_types.join(', ') + '\n';
                message += 'Actual Returned Types: ' + deviceValidation.expected_vs_actual.actual_returned_types.join(', ') + '\n';
            }
        }
        
        // Cross Validation
        if (validation.cross_validation) {
            message += '\n--- CROSS VALIDATION ---\n';
            message += 'Status: ' + validation.cross_validation.status + '\n';
            if (validation.cross_validation.consistency_checks) {
                var checks = validation.cross_validation.consistency_checks;
                message += 'Summary Total Clicks: ' + checks.summary_total_clicks + '\n';
                message += 'Timeline Total Clicks: ' + checks.timeline_total_clicks + '\n';
                message += 'Links Total Clicks: ' + checks.links_total_clicks + '\n';
                message += 'Summary vs Timeline Match: ' + (checks.summary_vs_timeline_match ? 'YES' : 'NO') + '\n';
                message += 'Summary vs Links Match: ' + (checks.summary_vs_links_match ? 'YES' : 'NO') + '\n';
            }
        }
        
        // Issues and Warnings
        if (validation.issues_found.length > 0) {
            message += '\n--- CRITICAL ISSUES FOUND ---\n';
            validation.issues_found.forEach(function(issue) {
                message += '• ' + issue + '\n';
            });
        }
        
        if (validation.warnings.length > 0) {
            message += '\n--- WARNINGS ---\n';
            validation.warnings.forEach(function(warning) {
                message += '• ' + warning + '\n';
            });
        }
        
        // Final Assessment
        message += '\n=== FINAL ASSESSMENT ===\n';
        if (validation.overall_status === 'PASSED') {
            message += '✅ ALL PANELS ARE TRUSTWORTHY - Data integrity verified across all dashboard components.\n';
        } else if (validation.overall_status === 'FAILED') {
            message += '❌ DATA INTEGRITY ISSUES FOUND - Some panels may show incorrect data. Review issues above.\n';
        } else {
            message += '⚠️ WARNINGS DETECTED - Panels are mostly reliable but some concerns exist.\n';
        }
        
        // Show results in a scrollable dialog
        showValidationDialog(message, validation.overall_status);
    }

    /**
     * Show validation results in a proper dialog
     */
    function showValidationDialog(message, status) {
        // Create modal dialog
        var modalHtml = '<div id="validation-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 999999; display: flex; align-items: center; justify-content: center;">';
        modalHtml += '<div style="background: white; padding: 20px; border-radius: 8px; max-width: 80%; max-height: 80%; overflow: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">';
        modalHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
        modalHtml += '<h2 style="margin: 0; color: ' + (status === 'PASSED' ? '#0073aa' : status === 'FAILED' ? '#d63638' : '#dba617') + ';">Data Validation Report</h2>';
        modalHtml += '<button id="close-validation-modal" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 5px;">×</button>';
        modalHtml += '</div>';
        modalHtml += '<pre style="white-space: pre-wrap; font-family: monospace; font-size: 12px; line-height: 1.4; max-height: 60vh; overflow: auto; background: #f5f5f5; padding: 15px; border-radius: 4px;">' + escapeHtml(message) + '</pre>';
        modalHtml += '<div style="margin-top: 15px; text-align: right;">';
        modalHtml += '<button id="copy-validation-report" class="button button-secondary" style="margin-right: 10px;">Copy Report</button>';
        modalHtml += '<button id="close-validation-modal-btn" class="button button-primary">Close</button>';
        modalHtml += '</div>';
        modalHtml += '</div></div>';
        
        $('body').append(modalHtml);
        
        // Bind close events
        $('#close-validation-modal, #close-validation-modal-btn').on('click', function() {
            $('#validation-modal').remove();
        });
        
        // Bind copy event
        $('#copy-validation-report').on('click', function() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(message).then(function() {
                    $(this).text('Copied!');
                    setTimeout(function() {
                        $('#copy-validation-report').text('Copy Report');
                    }, 2000);
                });
            }
        });
        
        // Close on background click
        $('#validation-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
    }

    /**
     * Load dashboard statistics
     */
    function loadDashboardStats(days, dateFrom, dateTo) {
        debugLog('Loading dashboard stats...', {days: days, dateFrom: dateFrom, dateTo: dateTo});
        
        if (!testBasicFunctionality()) {
            debugLog('Basic functionality test failed, aborting stats load');
            return;
        }
        
        var data = {
            action: 'wp_link_tracker_get_dashboard_stats',
            nonce: wpLinkTrackerAdmin.nonce
        };
        
        if (days) {
            data.days = days;
        }
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        debugLog('Sending AJAX request with data:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                debugLog('Dashboard stats response received:', response);
                
                if (response.success) {
                    var stats = response.data;
                    debugLog('Stats data:', stats);
                    
                    // Update summary values
                    $('#wplinktracker-total-clicks').text(stats.total_clicks || '0');
                    $('#wplinktracker-unique-visitors').text(stats.unique_visitors || '0');
                    $('#wplinktracker-active-links').text(stats.active_links || '0');
                    $('#wplinktracker-avg-conversion').text(stats.avg_conversion || '0%');
                    
                    // Update clicks over time chart
                    if (stats.clicks_over_time) {
                        debugLog('Updating clicks over time chart with data:', stats.clicks_over_time);
                        updateClicksOverTimeChart(stats.clicks_over_time);
                    }
                    
                    // Update top links table
                    if (stats.top_links) {
                        debugLog('Updating top links table with data:', stats.top_links);
                        updateTopLinksTable(stats.top_links);
                    }
                    
                    // Update top referrers table
                    if (stats.top_referrers) {
                        debugLog('Updating top referrers table with data:', stats.top_referrers);
                        updateTopReferrersTable(stats.top_referrers);
                    }
                    
                    // Update device chart - WITH FIXED TOOLTIPS
                    if (stats.device_data) {
                        debugLog('Updating device chart with data:', stats.device_data);
                        updateDeviceChart(stats.device_data);
                    }
                    
                    // Update browser chart - WITH FIXED TOOLTIPS
                    if (stats.browser_data) {
                        debugLog('Updating browser chart with data:', stats.browser_data);
                        updateBrowserChart(stats.browser_data);
                    }
                    
                    // Update OS chart - WITH FIXED TOOLTIPS
                    if (stats.os_data) {
                        debugLog('Updating OS chart with data:', stats.os_data);
                        updateOSChart(stats.os_data);
                    }
                    
                    debugLog('Dashboard stats updated successfully');
                } else {
                    console.error('[WP Link Tracker] Dashboard stats failed:', response.data);
                    showError('Failed to load dashboard stats: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('[WP Link Tracker] AJAX request failed:', status, error);
                debugLog('XHR response:', xhr.responseText);
                showError('AJAX request failed: ' + status + ' - ' + error);
            });
    }

    /**
     * Update clicks over time chart
     */
    function updateClicksOverTimeChart(data) {
        debugLog('Updating clicks over time chart...', data);
        
        if (!chartInstances.clicksChart) {
            debugLog('Clicks chart instance not found, reinitializing...');
            initCharts();
            return;
        }
        
        if (!data || !Array.isArray(data)) {
            debugLog('Invalid data for clicks chart:', data);
            return;
        }
        
        try {
            var labels = [];
            var values = [];
            
            data.forEach(function(item) {
                if (item.date && typeof item.clicks !== 'undefined') {
                    labels.push(item.date);
                    values.push(parseInt(item.clicks) || 0);
                }
            });
            
            debugLog('Clicks Chart - Labels:', labels);
            debugLog('Clicks Chart - Values:', values);
            
            // Update chart data
            chartInstances.clicksChart.data.labels = labels;
            chartInstances.clicksChart.data.datasets[0].data = values;
            chartInstances.clicksChart.data.datasets[0].label = 'Clicks';
            chartInstances.clicksChart.data.datasets[0].backgroundColor = 'rgba(0, 115, 170, 0.2)';
            chartInstances.clicksChart.data.datasets[0].borderColor = 'rgba(0, 115, 170, 1)';
            
            // Update chart
            chartInstances.clicksChart.update();
            
            debugLog('Clicks over time chart updated successfully');
            
        } catch (error) {
            console.error('[WP Link Tracker] Failed to update clicks chart:', error);
        }
    }

    /**
     * Update top links table
     */
    function updateTopLinksTable(data) {
        debugLog('Updating top links table...', data);
        
        if (!data || !Array.isArray(data) || data.length === 0) {
            $('#wplinktracker-top-links-table').html('<p>No links data available yet.</p>');
            return;
        }
        
        var html = '<table class="widefat striped">';
        html += '<thead><tr>';
        html += '<th>Link Title</th>';
        html += '<th>Short URL</th>';
        html += '<th>Clicks</th>';
        html += '<th>Unique Visitors</th>';
        html += '<th>Conversion Rate</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        data.forEach(function(link) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(link.title) + '</strong></td>';
            html += '<td><a href="' + escapeHtml(link.short_url) + '" target="_blank">' + escapeHtml(link.short_url) + '</a></td>';
            html += '<td>' + link.total_clicks + '</td>';
            html += '<td>' + link.unique_visitors + '</td>';
            html += '<td>' + link.conversion_rate + '%</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#wplinktracker-top-links-table').html(html);
        debugLog('Top links table updated successfully');
    }

    /**
     * Update top referrers table
     */
    function updateTopReferrersTable(data) {
        debugLog('Updating top referrers table...', data);
        
        if (!data || !Array.isArray(data) || data.length === 0) {
            $('#wplinktracker-top-referrers-table').html('<p>No referrer data available yet.</p>');
            return;
        }
        
        var html = '<table class="widefat striped">';
        html += '<thead><tr>';
        html += '<th>Referrer</th>';
        html += '<th>Clicks</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        data.forEach(function(referrer) {
            html += '<tr>';
            html += '<td>' + escapeHtml(referrer.domain) + '</td>';
            html += '<td>' + referrer.count + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#wplinktracker-top-referrers-table').html(html);
        debugLog('Top referrers table updated successfully');
    }

    /**
     * Update device chart - WITH FIXED TOOLTIP HANDLING
     */
    function updateDeviceChart(data) {
        debugLog('Updating device chart with fixed tooltips...', data);
        
        if (!chartInstances.devicesChart) {
            debugLog('Device chart instance not found');
            return;
        }
        
        if (!data || !Array.isArray(data) || data.length === 0) {
            debugLog('No device data available, showing empty state');
            chartInstances.devicesChart.data.labels = ['No Data'];
            chartInstances.devicesChart.data.datasets[0].data = [1];
            chartInstances.devicesChart.data.datasets[0].backgroundColor = ['#e0e0e0'];
            chartInstances.devicesChart.update();
            return;
        }
        
        try {
            var labels = [];
            var values = [];
            var colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'];
            
            data.forEach(function(item, index) {
                var deviceLabel = item.device || 'Unknown';
                labels.push(deviceLabel);
                values.push(parseInt(item.count) || 0);
            });
            
            debugLog('Device Chart - Labels:', labels);
            debugLog('Device Chart - Values:', values);
            
            // CRITICAL: Completely rebuild chart data to ensure tooltip consistency
            chartInstances.devicesChart.data = {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length)
                }]
            };
            
            chartInstances.devicesChart.update();
            debugLog('Device chart updated successfully with fixed tooltips');
            
        } catch (error) {
            console.error('[WP Link Tracker] Failed to update device chart:', error);
        }
    }

    /**
     * Update browser chart - WITH FIXED TOOLTIP HANDLING
     */
    function updateBrowserChart(data) {
        debugLog('Updating browser chart with fixed tooltips...', data);
        
        if (!chartInstances.browsersChart) {
            debugLog('Browser chart instance not found');
            return;
        }
        
        if (!data || !Array.isArray(data) || data.length === 0) {
            debugLog('No browser data available, showing empty state');
            chartInstances.browsersChart.data.labels = ['No Data'];
            chartInstances.browsersChart.data.datasets[0].data = [1];
            chartInstances.browsersChart.data.datasets[0].backgroundColor = ['#e0e0e0'];
            chartInstances.browsersChart.update();
            return;
        }
        
        try {
            var labels = [];
            var values = [];
            var colors = ['#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56', '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'];
            
            data.forEach(function(item, index) {
                var browserLabel = item.browser || 'Unknown';
                labels.push(browserLabel);
                values.push(parseInt(item.count) || 0);
            });
            
            debugLog('Browser Chart - Labels:', labels);
            debugLog('Browser Chart - Values:', values);
            
            // CRITICAL: Completely rebuild chart data to ensure tooltip consistency
            chartInstances.browsersChart.data = {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length)
                }]
            };
            
            chartInstances.browsersChart.update();
            debugLog('Browser chart updated successfully with fixed tooltips');
            
        } catch (error) {
            console.error('[WP Link Tracker] Failed to update browser chart:', error);
        }
    }

    /**
     * Update OS chart - WITH FIXED TOOLTIP HANDLING
     */
    function updateOSChart(data) {
        debugLog('Updating OS chart with fixed tooltips...', data);
        
        if (!chartInstances.osChart) {
            debugLog('OS chart instance not found');
            return;
        }
        
        if (!data || !Array.isArray(data) || data.length === 0) {
            debugLog('No OS data available, showing empty state');
            chartInstances.osChart.data.labels = ['No Data'];
            chartInstances.osChart.data.datasets[0].data = [1];
            chartInstances.osChart.data.datasets[0].backgroundColor = ['#e0e0e0'];
            chartInstances.osChart.update();
            return;
        }
        
        try {
            var labels = [];
            var values = [];
            var colors = ['#9966FF', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#FF9F40', '#FF6384', '#C9CBCF'];
            
            data.forEach(function(item, index) {
                var osLabel = item.os || 'Unknown';
                labels.push(osLabel);
                values.push(parseInt(item.count) || 0);
            });
            
            debugLog('OS Chart - Labels:', labels);
            debugLog('OS Chart - Values:', values);
            
            // CRITICAL: Completely rebuild chart data to ensure tooltip consistency
            chartInstances.osChart.data = {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length)
                }]
            };
            
            chartInstances.osChart.update();
            debugLog('OS chart updated successfully with fixed tooltips');
            
        } catch (error) {
            console.error('[WP Link Tracker] Failed to update OS chart:', error);
        }
    }

    /**
     * Escape HTML for safe display
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '<',
            '>': '>',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Handle refresh dashboard
     */
    function handleRefreshDashboard() {
        var selectedRange = $('#wplinktracker-date-range-select').val();
        
        if (selectedRange === 'custom') {
            var fromDate = $('#wplinktracker-date-from').val();
            var toDate = $('#wplinktracker-date-to').val();
            
            if (fromDate && toDate) {
                loadDashboardStats(null, fromDate, toDate);
            } else {
                loadDashboardStats(30);
            }
        } else {
            loadDashboardStats(selectedRange);
        }
    }

    /**
     * Handle refresh data
     */
    function handleRefreshData() {
        handleRefreshDashboard();
    }

    /**
     * Handle view data count
     */
    function handleViewDataCount() {
        debugLog('Handling view data count...');
        
        var selectedRange = $('#wplinktracker-date-range-select').val();
        var days = selectedRange !== 'custom' ? selectedRange : 30;
        var dateFrom = '';
        var dateTo = '';
        
        if (selectedRange === 'custom') {
            dateFrom = $('#wplinktracker-date-from').val();
            dateTo = $('#wplinktracker-date-to').val();
        }
        
        var data = {
            action: 'wp_link_tracker_view_data_count',
            nonce: wpLinkTrackerAdmin.nonce,
            days: days
        };
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        debugLog('Sending view data count request:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                debugLog('View data count response:', response);
                
                if (response.success) {
                    var count = response.data;
                    var message = 'Data Count Information:\n\n';
                    message += 'Tracked Links: ' + count.tracked_links + '\n';
                    message += 'Clicks Table Exists: ' + (count.clicks_table_exists ? 'Yes' : 'No') + '\n';
                    message += 'Total Click Records: ' + count.total_click_records + '\n';
                    message += 'Filtered Click Records: ' + count.filtered_click_records + '\n\n';
                    message += 'Current Date Range:\n';
                    message += 'Days: ' + count.date_range.days + '\n';
                    message += 'Date From: ' + count.date_range.date_from + '\n';
                    message += 'Date To: ' + count.date_range.date_to + '\n';
                    
                    alert(message);
                } else {
                    alert('View data count failed: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('[WP Link Tracker] View data count failed:', status, error);
                alert('View data count request failed: ' + status + ' - ' + error);
            });
    }

    /**
     * Handle debug date range
     */
    function handleDebugDateRange() {
        debugLog('Handling debug date range...');
        
        var selectedRange = $('#wplinktracker-date-range-select').val();
        var days = selectedRange !== 'custom' ? selectedRange : 30;
        var dateFrom = '';
        var dateTo = '';
        
        if (selectedRange === 'custom') {
            dateFrom = $('#wplinktracker-date-from').val();
            dateTo = $('#wplinktracker-date-to').val();
        }
        
        var data = {
            action: 'wp_link_tracker_debug_date_range',
            nonce: wpLinkTrackerAdmin.nonce,
            days: days
        };
        
        if (dateFrom && dateTo) {
            data.date_from = dateFrom;
            data.date_to = dateTo;
        }
        
        debugLog('Sending debug request:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                debugLog('Debug response:', response);
                
                if (response.success) {
                    var debug = response.data;
                    var message = 'Debug Information:\n\n';
                    message += 'Current Time: ' + debug.current_time + '\n';
                    message += 'Timezone: ' + debug.timezone + '\n';
                    message += 'Date Format: ' + debug.date_format + '\n';
                    message += 'Time Format: ' + debug.time_format + '\n\n';
                    message += 'Parameters:\n';
                    message += 'Days: ' + debug.parameters.days + '\n';
                    message += 'Date From: ' + debug.parameters.date_from + '\n';
                    message += 'Date To: ' + debug.parameters.date_to + '\n\n';
                    message += 'Clicks Table Exists: ' + (debug.clicks_table_exists ? 'Yes' : 'No') + '\n';
                    message += 'Total Click Records: ' + debug.total_click_records + '\n';
                    
                    if (debug.sample_records && debug.sample_records.length > 0) {
                        message += '\nSample Records:\n';
                        debug.sample_records.forEach(function(record, index) {
                            message += (index + 1) + '. Post ID: ' + record.post_id + ', Time: ' + record.click_time + ', IP: ' + record.ip_address + '\n';
                        });
                    }
                    
                    if (debug.clicks_over_time_data) {
                        message += '\nClicks Over Time Data:\n';
                        debug.clicks_over_time_data.forEach(function(item, index) {
                            if (index < 5) { // Show first 5 entries
                                message += item.date + ': ' + item.clicks + ' clicks\n';
                            }
                        });
                        if (debug.clicks_over_time_data.length > 5) {
                            message += '... and ' + (debug.clicks_over_time_data.length - 5) + ' more entries\n';
                        }
                    }
                    
                    alert(message);
                } else {
                    alert('Debug failed: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('[WP Link Tracker] Debug request failed:', status, error);
                alert('Debug request failed: ' + status + ' - ' + error);
            });
    }

    /**
     * Handle reset stats
     */
    function handleResetStats() {
        debugLog('Handling reset stats...');
        
        var confirmMessage = wpLinkTrackerAdmin.resetConfirmMessage || 'Are you sure you want to reset all statistics? This action cannot be undone.';
        
        if (!confirm(confirmMessage)) {
            debugLog('Reset cancelled by user');
            return;
        }
        
        var data = {
            action: 'wp_link_tracker_reset_stats',
            nonce: wpLinkTrackerAdmin.nonce
        };
        
        debugLog('Sending reset request:', data);
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, data)
            .done(function(response) {
                debugLog('Reset response:', response);
                
                if (response.success) {
                    var successMessage = wpLinkTrackerAdmin.resetSuccessMessage || 'Statistics reset successfully!';
                    alert(successMessage + '\n\nDetails: ' + response.data.message);
                    
                    // Reload dashboard stats
                    debugLog('Reloading dashboard stats after reset...');
                    loadDashboardStats();
                } else {
                    var errorMessage = wpLinkTrackerAdmin.resetErrorMessage || 'Reset failed. Please try again.';
                    alert(errorMessage + '\n\nError: ' + response.data);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('[WP Link Tracker] Reset request failed:', status, error);
                debugLog('XHR response:', xhr.responseText);
                var errorMessage = wpLinkTrackerAdmin.resetErrorMessage || 'Reset failed. Please try again.';
                alert(errorMessage + '\n\nTechnical error: ' + status + ' - ' + error);
            });
    }

    /**
     * Handle apply custom date range
     */
    function handleApplyCustomDateRange() {
        var fromDate = $('#wplinktracker-date-from').val();
        var toDate = $('#wplinktracker-date-to').val();
        
        if (!fromDate || !toDate) {
            alert('Please select both from and to dates.');
            return;
        }
        
        debugLog('Applying custom date range:', fromDate, 'to', toDate);
        loadDashboardStats(null, fromDate, toDate);
    }

    /**
     * Show error message
     */
    function showError(message) {
        debugLog('Showing error:', message);
        
        // Update summary values to show error
        $('#wplinktracker-total-clicks').text('Error');
        $('#wplinktracker-unique-visitors').text('Error');
        $('#wplinktracker-active-links').text('Error');
        $('#wplinktracker-avg-conversion').text('Error');
    }

    // Global function for drawing charts (if needed by other parts)
    window.drawClicksChart = function(data) {
        debugLog('Drawing clicks chart with data:', data);
        updateClicksOverTimeChart(data);
    };

})(jQuery);
