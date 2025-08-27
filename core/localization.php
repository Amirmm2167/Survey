<?php
// core/localization.php

// This file should be included after session_start() is called.
require_once __DIR__ . '/../config/config.php';

// Determine the language to load
$current_lang = DEFAULT_LANG; // Start with the default

// Allow language switching via URL (e.g., /index.php?lang=en)
if (isset($_GET['lang']) && is_string($_GET['lang'])) {
    // Basic validation to ensure the lang file exists
    if (file_exists(__DIR__ . '/../lang/' . $_GET['lang'] . '.php')) {
        $current_lang = $_GET['lang'];
        // Store the user's preference in the session for persistence
        $_SESSION['lang'] = $current_lang;
    }
} elseif (isset($_SESSION['lang']) && is_string($_SESSION['lang'])) {
    // Check if the language is already set in the session
    if (file_exists(__DIR__ . '/../lang/' . $_SESSION['lang'] . '.php')) {
        $current_lang = $_SESSION['lang'];
    }
}

// Load the language file
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // Fallback to English if the selected language file is missing for some reason
    require_once __DIR__ . '/../lang/en.php';
}

/**
 * Gets a translated string.
 * @param string $key The key for the language string.
 * @return string The translated string or the key itself if not found.
 */
function trans($key) {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $key;
}

// Global variable to determine page direction (for RTL/LTR in templates)
$page_direction = ($current_lang === 'fa') ? 'rtl' : 'ltr';
?>
