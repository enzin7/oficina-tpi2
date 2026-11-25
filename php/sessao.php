<?php
require __DIR__ . '/funcoes.php';
$usuario = exigirLogin();
responder(true, $usuario);
