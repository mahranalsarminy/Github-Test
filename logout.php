<?php
// Include initialization file
require_once(__DIR__ . '/includes/init.php');

// Log user out
logout_user();

// Redirect to home page
redirect('/');