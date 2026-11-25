<?php
require __DIR__ . '/funcoes.php';
exigirPost();
exigirLogin();
$id = idEdicao();

$erros = validarObrigatorios([
    'cpf_cliente' => 'CPF do proprietário', 'placa' => 'Placa', 'marca' => 'Marca',
    'modelo' => 'Modelo', 'ano' => 'Ano de fabricação', 'quilometragem' => 'Quilometragem',
]);

$cpf = apenasDigitos(campo('cpf_cliente'));
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF do proprietário inválido.';

$placa = normalizarPlaca(campo('placa'));
if ($placa !== '' && !placaValida($placa)) $erros[] = 'Placa inválida. Use ABC1234 ou ABC1D23 (Mercosul).';

$anoAtual = (int) date('Y');
$ano = (int) campo('ano');
if (campo('ano') !== '' && ($ano < 1950 || $ano > $anoAtual + 1)) $erros[] = 'Ano deve estar entre 1950 e ' . ($anoAtual + 1) . '.';

$km = numero(campo('quilometragem'));
if (campo('quilometragem') !== '' && ($km === null || $km < 0)) $erros[] = 'Quilometragem deve ser um número positivo.';

if (!$erros) {
    $cliente = buscar('SELECT id, nome FROM cliente WHERE cpf = ?', [$cpf]);
    if (!$cliente) $erros[] = 'Nenhum cliente cadastrado com este CPF. Cadastre o cliente primeiro.';
}

if ($erros) responder(false, $erros);

$id = salvar('veiculo', [
    'cliente_id' => $cliente['id'],
    'placa' => $placa,
    'marca' => campo('marca'),
    'modelo' => campo('modelo'),
    'ano' => $ano,
    'quilometragem' => (int) $km,
    'combustivel' => campo('combustivel') ?: null,
], $id, 'Já existe um veículo cadastrado com esta placa.');

// Média de km/ano e próxima revisão (a cada 10.000 km).
$idade = max(1, $anoAtual - $ano);
$proximaRevisao = (floor($km / 10000) + 1) * 10000;

responder(true, [
    'Código' => $id,
    'Proprietário' => $cliente['nome'],
    'Placa' => $placa,
    'Padrão da placa' => ctype_alpha($placa[4]) ? 'Mercosul' : 'Antigo',
    'Veículo' => campo('marca') . ' ' . campo('modelo') . " ($ano)",
    'Combustível' => campo('combustivel') ?: 'Não informado',
    'Média de uso' => number_format($km / $idade, 0, ',', '.') . ' km/ano',
    'Próxima revisão' => number_format($proximaRevisao, 0, ',', '.') . ' km',
], 200, idEdicao() ? 'Veículo atualizado com sucesso!' : 'Veículo cadastrado com sucesso!');
