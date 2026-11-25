<?php
// Recebe a abertura de ORDEM DE SERVIÇO (tabela: ordem_servico, ligada a
// cliente, veiculo, mecanico, servico e usuario).
require __DIR__ . '/funcoes.php';
exigirPost();
$usuario = exigirLogin();

$erros = validarObrigatorios([
    'cpf_cliente' => 'CPF do cliente', 'placa' => 'Placa', 'mecanico_id' => 'Mecânico responsável',
    'servico' => 'Tipo de serviço', 'data_entrada' => 'Data de entrada', 'data_previsao' => 'Previsão de entrega',
    'horas' => 'Horas de mão de obra', 'valor_pecas' => 'Valor das peças',
]);

$cpf = apenasDigitos(campo('cpf_cliente'));
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF do cliente inválido.';

$placa = normalizarPlaca(campo('placa'));
if ($placa !== '' && !placaValida($placa)) $erros[] = 'Placa inválida.';

$entrada = dataValida(campo('data_entrada'));
$previsao = dataValida(campo('data_previsao'));
if (campo('data_entrada') !== '' && !$entrada) $erros[] = 'Data de entrada inválida.';
if (campo('data_previsao') !== '' && !$previsao) $erros[] = 'Previsão de entrega inválida.';
if ($entrada && $previsao && $previsao < $entrada) $erros[] = 'A previsão de entrega não pode ser anterior à entrada.';

$horas = numero(campo('horas'));
if (campo('horas') !== '' && ($horas === null || $horas <= 0 || $horas > 200)) $erros[] = 'Horas de mão de obra devem ficar entre 0 e 200.';

$valorPecas = numero(campo('valor_pecas'));
if (campo('valor_pecas') !== '' && ($valorPecas === null || $valorPecas < 0)) $erros[] = 'Valor das peças não pode ser negativo.';

$desconto = numero(campo('desconto') ?: '0');
if ($desconto === null || $desconto < 0 || $desconto > 15) $erros[] = 'Desconto deve estar entre 0% e 15%.';

if ($erros) responder(false, $erros);

// Busca no banco os registros relacionados (chaves estrangeiras).
$cliente = buscar('SELECT id, nome FROM cliente WHERE cpf = ?', [$cpf]);
if (!$cliente) $erros[] = 'Nenhum cliente cadastrado com este CPF.';

$veiculo = buscar('SELECT id, cliente_id, marca, modelo FROM veiculo WHERE placa = ?', [$placa]);
if (!$veiculo) {
    $erros[] = 'Nenhum veículo cadastrado com esta placa.';
} elseif ($cliente && (int) $veiculo['cliente_id'] !== (int) $cliente['id']) {
    $erros[] = 'Este veículo não pertence ao cliente informado.';
}

$mecanico = buscar('SELECT id, nome FROM mecanico WHERE id = ?', [(int) campo('mecanico_id')]);
if (!$mecanico) $erros[] = 'Mecânico não encontrado.';

$servico = buscar('SELECT id, nome, valor_hora FROM servico WHERE codigo = ?', [campo('servico')]);
if (!$servico) $erros[] = 'Tipo de serviço inválido.';

if ($erros) responder(false, $erros);

// Lógica adicional: orçamento total com desconto e prazo em dias.
$valorHora = (float) $servico['valor_hora'];
$maoDeObra = $horas * $valorHora;
$subtotal = $maoDeObra + $valorPecas;
$total = round($subtotal * (1 - $desconto / 100), 2);
$prazo = $entrada->diff($previsao)->days;

$id = inserir(
    'INSERT INTO ordem_servico (cliente_id, veiculo_id, mecanico_id, servico_id, usuario_id, data_entrada,
        data_previsao, horas, valor_pecas, desconto, total, relato) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [$cliente['id'], $veiculo['id'], $mecanico['id'], $servico['id'], $usuario['id'], campo('data_entrada'),
        campo('data_previsao'), $horas, $valorPecas, $desconto, $total, campo('relato') ?: null],
    'Não foi possível abrir a ordem de serviço.'
);

responder(true, [
    'Número da OS' => $id,
    'Cliente' => $cliente['nome'] . ' (' . formatarCpf($cpf) . ')',
    'Veículo' => "$placa — {$veiculo['marca']} {$veiculo['modelo']}",
    'Mecânico' => $mecanico['nome'],
    'Serviço' => $servico['nome'],
    'Mão de obra' => number_format($horas, 1, ',', '') . ' h x ' . moeda($valorHora) . ' = ' . moeda($maoDeObra),
    'Peças' => moeda($valorPecas),
    'Desconto' => number_format($desconto, 1, ',', '') . '%',
    'Total do orçamento' => moeda($total),
    'Prazo' => "$prazo dia(s)",
    'Aberta por' => $usuario['nome'],
], 200, 'Ordem de serviço aberta com sucesso!');
