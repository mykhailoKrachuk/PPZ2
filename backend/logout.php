<?php
require __DIR__ . '/config.php';
$_SESSION = [];
if (session_id() !== '') session_destroy();
header('Location: /Salfetka/login.html');
