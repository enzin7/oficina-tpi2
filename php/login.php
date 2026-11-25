<?php
// Autentica o usuário (tabela: usuario) e cria a sessão.
require __DIR__ . '/funcoes.php';
exigirPost();
iniciarSessao();

$erros = validarObrigatorios(['login' => 'Usuário', 'senha' => 'Senha']);
if ($erros) responder(false, $erros);

$usuario = buscar('SELECT id, nome, login, senha_hash FROM usuario WHERE login = ?', [campo('login')]);

// Mesma mensagem para usuário inexistente ou senha errada (não revela qual dos dois).
if (!$usuario || !password_verify($_POST['senha'], $usuario['senha_hash'])) {
    responder(false, ['Usuário ou senha incorretos.'], 401);
}

session_regenerate_id(true); // evita fixação de sessão
$_SESSION['usuario'] = ['id' => (int) $usuario['id'], 'nome' => $usuario['nome'], 'login' => $usuario['login']];
$_SESSION['ultimo_acesso'] = time();

responder(true, ['Usuário' => $usuario['nome']], 200, 'Login realizado com sucesso!');
