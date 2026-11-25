<?php
// Informa ao JavaScript se existe usuário logado (usado para proteger as páginas).
require __DIR__ . '/funcoes.php';
$usuario = exigirLogin();
responder(true, $usuario);
