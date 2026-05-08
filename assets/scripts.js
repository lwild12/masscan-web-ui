/* =========================================================
 * Masscan Web UI — scripts.js
 * Bootstrap 5 + jQuery 3
 * ========================================================= */

var delayTimer;

/* ---------------------------------------------------------
 * Dark mode toggle
 * --------------------------------------------------------- */
(function () {
    var html   = document.documentElement;
    var saved  = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', saved);

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('theme-toggle');
        if (!btn) return;
        updateToggleIcon(saved);
        btn.addEventListener('click', function () {
            var current = html.getAttribute('data-bs-theme');
            var next    = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('theme', next);
            updateToggleIcon(next);
        });
    });

    function updateToggleIcon(theme) {
        var btn = document.getElementById('theme-toggle');
        if (!btn) return;
        var icon = btn.querySelector('i');
        if (!icon) return;
        icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    }
})();

/* ---------------------------------------------------------
 * Search panel collapse — show active filter summary
 * --------------------------------------------------------- */
jQuery(document).ready(function () {
    var collapseEl = document.getElementById('collapse');
    if (!collapseEl) return;

    collapseEl.addEventListener('show.bs.collapse', function () {
        jQuery('#search-params').html('');
        jQuery('#collapse-icon').removeClass('bi-plus-lg').addClass('bi-dash-lg');
    });

    collapseEl.addEventListener('hide.bs.collapse', function () {
        jQuery('#collapse-icon').removeClass('bi-dash-lg').addClass('bi-plus-lg');

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        var parts = [];
        if (jQuery('#ipAddress').val())    parts.push('IP: <strong>'           + escHtml(jQuery('#ipAddress').val())    + '</strong>');
        if (jQuery('#portN').val())        parts.push('Port: <strong>'         + escHtml(jQuery('#portN').val())        + '</strong>');
        if (jQuery('#serviceState').val()) parts.push('State: <strong>'        + escHtml(jQuery('#serviceState').val()) + '</strong>');
        if (jQuery('#pProtocol').val())    parts.push('Protocol: <strong>'     + escHtml(jQuery('#pProtocol').val())    + '</strong>');
        if (jQuery('#pService').val())     parts.push('Service: <strong>'      + escHtml(jQuery('#pService').val())     + '</strong>');
        if (jQuery('#pBanner').val())      parts.push('Banner/Title: <strong>' + escHtml(jQuery('#pBanner').val())      + '</strong>');

        if (parts.length > 0) {
            jQuery('#search-params').html(
                '<p class="text-muted small mb-0 ms-2">' + parts.join(' | ') + '</p>'
            );
        }
    });
});

/* ---------------------------------------------------------
 * Form search submit (via AJAX)
 * --------------------------------------------------------- */
function submitSearchForm()
{
    var data = jQuery('#form').serialize() + '&form=1';
    jQuery.ajax({
        beforeSend: function () { jQuery('#ajax-loader-form').removeClass('d-none'); },
        complete:   function () { jQuery('#ajax-loader-form').addClass('d-none'); },
        error: function () { alert('There was an error during the request. Please try again.'); },
        success: function (response) {
            jQuery('#ajax-search-container').html(response);
            jQuery('a#export-link').off('click').on('click', function () {
                exportResultsToXML(data);
                return false;
            });
        },
        timeout:  100000,
        type:     'get',
        dataType: 'html',
        data:     data,
        url:      './filter.php'
    });
    return false;
}

/* ---------------------------------------------------------
 * IP history modal
 * --------------------------------------------------------- */
function showIpHistory(ip, ipa)
{
    jQuery('#myModalLabel').text('Scan history for IP ' + ip);
    jQuery.ajax({
        error: function () { alert('There was an error during the request. Please try again.'); },
        success: function (response) {
            jQuery('.modal-body').html(response);
            var modal = new bootstrap.Modal(document.getElementById('myModal'));
            modal.show();
        },
        timeout:  100000,
        type:     'get',
        dataType: 'html',
        data:     'ip=' + ipa,
        url:      './ajax.php'
    });
    return false;
}

/* ---------------------------------------------------------
 * XML export (hidden iframe download)
 * --------------------------------------------------------- */
function exportResultsToXML(data)
{
    $('<iframe />')
        .attr('src', './export.php?' + data)
        .hide()
        .appendTo('body');
    return false;
}

/* ---------------------------------------------------------
 * Debounced quick-search
 * --------------------------------------------------------- */
function searchDataText(data)
{
    clearTimeout(delayTimer);
    delayTimer = setTimeout(function () { searchData(data); }, 1000);
}

/* ---------------------------------------------------------
 * Generic AJAX search / pagination
 * --------------------------------------------------------- */
function searchData(data, throbber)
{
    throbber = throbber || 'ajax-loader';
    jQuery.ajax({
        beforeSend: function () { jQuery('#' + throbber).removeClass('d-none'); },
        complete:   function () { jQuery('#' + throbber).addClass('d-none'); },
        error: function () { alert('There was an error during the request. Please try again.'); },
        success: function (response) {
            jQuery('#ajax-list-container').html(response);
            jQuery('a#export-link').off('click').on('click', function () {
                exportResultsToXML(data);
                return false;
            });
        },
        timeout:  100000,
        type:     'get',
        dataType: 'html',
        data:     data,
        url:      './filter.php'
    });
    return false;
}

/* ---------------------------------------------------------
 * Import help modal
 * --------------------------------------------------------- */
function showImportHelp()
{
    jQuery('#myModalLabel').text('How to scan and import data?');
    jQuery.ajax({
        error: function () { alert('There was an error during the request. Please try again.'); },
        success: function (response) {
            jQuery('.modal-body').html(response);
            var modal = new bootstrap.Modal(document.getElementById('myModal'));
            modal.show();
        },
        timeout:  100000,
        type:     'get',
        dataType: 'html',
        data:     '',
        url:      './includes/html/import-help.html'
    });
    return false;
}
