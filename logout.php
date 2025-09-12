<?php
require_once __DIR__ . '/config.php';

// Clear session and redirect to login
session_unset();
session_destroy();

header("Location: login.php");
exit;
