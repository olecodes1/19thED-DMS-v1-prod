<?php
require_once '../includes/auth.php';

ensure_session_started();
$_SESSION = [];
session_destroy();

header('Location: ../login.php');
exit;

