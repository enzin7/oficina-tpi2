<?php
require __DIR__ . '/funcoes.php';
exigirPost();
iniciarSessao();

$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
session_destroy();

responder(true, [], 200, 'Sessão encerrada.');
