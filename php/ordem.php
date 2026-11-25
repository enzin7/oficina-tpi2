<?php
require __DIR__ . '/funcoes.php';
exigirPost();
$usuario = exigirLogin();
$id = idEdicao();

$erros = validarObrigatorios([
    'cpf_cliente' => 'CPF do cliente', 'placa' => 'Placa', 'mecanico_id' => 'Mecânico responsável',
    'servico' => 'Tipo de serviço', 'data_entrada' => 'Data de entrada', 'data_previsao' => 'Previsão de entrega',
    'horas' => 'Horas de mão de obra',
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

$desconto = numero(campo('desconto') ?: '0');
if ($desconto === null || $desconto < 0 || $desconto > 15) $erros[] = 'Desconto deve estar entre 0% e 15%.';

// Peças chegam como pecas[id_da_peca] = quantidade.
$pecasPedidas = [];
foreach ((array) ($_POST['pecas'] ?? []) as $pecaId => $qtd) {
    if (!ctype_digit((string) $pecaId) || !ctype_digit((string) $qtd) || (int) $qtd < 1) {
        $erros[] = 'Quantidade de peça inválida.';
        break;
    }
    $pecasPedidas[(int) $pecaId] = (int) $qtd;
}

if ($erros) responder(false, $erros);

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

conexao()->beginTransaction();

// Na edição, as peças antigas voltam ao estoque antes de lançar as novas.
if ($id !== null) {
    if (!buscar('SELECT id FROM ordem_servico WHERE id = ? FOR UPDATE', [$id])) {
        responder(false, ['Ordem de serviço não encontrada.'], 404);
    }
    devolverPecasDaOrdem($id);
}

$itens = [];
$valorPecas = 0;
foreach ($pecasPedidas as $pecaId => $qtd) {
    $peca = buscar('SELECT id, codigo, descricao, quantidade, preco_custo, margem FROM peca WHERE id = ? FOR UPDATE', [$pecaId]);
    if (!$peca) {
        $erros[] = "Peça #$pecaId não encontrada.";
        continue;
    }
    if ((int) $peca['quantidade'] < $qtd) {
        $erros[] = "Estoque insuficiente de {$peca['descricao']} (disponível: {$peca['quantidade']}).";
        continue;
    }
    $preco = round($peca['preco_custo'] * (1 + $peca['margem'] / 100), 2);
    $valorPecas += $preco * $qtd;
    $itens[] = ['peca' => $peca, 'qtd' => $qtd, 'preco' => $preco];
}

if ($erros) responder(false, $erros);

$valorHora = (float) $servico['valor_hora'];
$maoDeObra = $horas * $valorHora;
$total = round(($maoDeObra + $valorPecas) * (1 - $desconto / 100), 2);
$prazo = $entrada->diff($previsao)->days;

$dados = [
    'cliente_id' => $cliente['id'],
    'veiculo_id' => $veiculo['id'],
    'mecanico_id' => $mecanico['id'],
    'servico_id' => $servico['id'],
    'data_entrada' => campo('data_entrada'),
    'data_previsao' => campo('data_previsao'),
    'horas' => $horas,
    'valor_pecas' => $valorPecas,
    'desconto' => $desconto,
    'total' => $total,
    'relato' => campo('relato') ?: null,
];
if ($id === null) $dados['usuario_id'] = $usuario['id'];
$id = salvar('ordem_servico', $dados, $id, 'Não foi possível salvar a ordem de serviço.');

$listaPecas = [];
foreach ($itens as $item) {
    executar(
        'INSERT INTO ordem_servico_peca (ordem_servico_id, peca_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)',
        [$id, $item['peca']['id'], $item['qtd'], $item['preco']]
    );
    executar('UPDATE peca SET quantidade = quantidade - ? WHERE id = ?', [$item['qtd'], $item['peca']['id']]);
    $listaPecas[] = "{$item['qtd']}x {$item['peca']['descricao']} (" . moeda($item['preco'] * $item['qtd']) . ')';
}

conexao()->commit();

responder(true, [
    'Número da OS' => $id,
    'Cliente' => $cliente['nome'] . ' (' . formatarCpf($cpf) . ')',
    'Veículo' => "$placa — {$veiculo['marca']} {$veiculo['modelo']}",
    'Mecânico' => $mecanico['nome'],
    'Serviço' => $servico['nome'],
    'Mão de obra' => number_format($horas, 1, ',', '') . ' h x ' . moeda($valorHora) . ' = ' . moeda($maoDeObra),
    'Peças' => $listaPecas ? implode('; ', $listaPecas) . ' = ' . moeda($valorPecas) : 'Nenhuma',
    'Desconto' => number_format($desconto, 1, ',', '') . '%',
    'Total do orçamento' => moeda($total),
    'Prazo' => "$prazo dia(s)",
], 200, idEdicao() ? 'Ordem de serviço atualizada com sucesso!' : 'Ordem de serviço aberta com sucesso!');
