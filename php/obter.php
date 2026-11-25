<?php
// Devolve um registro com os nomes dos campos do formulário, para edição.
require __DIR__ . '/funcoes.php';
exigirLogin();

$consultas = [
    'clientes' => 'SELECT id, nome, cpf, data_nascimento, telefone, email, cidade, origem FROM cliente WHERE id = ?',
    'veiculos' => 'SELECT v.id, c.cpf AS cpf_cliente, v.placa, v.marca, v.modelo, v.ano, v.quilometragem, v.combustivel
                   FROM veiculo v JOIN cliente c ON c.id = v.cliente_id WHERE v.id = ?',
    'mecanicos' => 'SELECT id, nome, cpf, telefone, especialidade, valor_hora, data_admissao FROM mecanico WHERE id = ?',
    'pecas' => 'SELECT id, codigo, descricao, fabricante, preco_custo, margem, quantidade, estoque_minimo FROM peca WHERE id = ?',
    'ordens' => 'SELECT o.id, c.cpf AS cpf_cliente, v.placa, o.mecanico_id, s.codigo AS servico, o.data_entrada,
                        o.data_previsao, o.horas, o.desconto, o.relato
                 FROM ordem_servico o
                 JOIN cliente c ON c.id = o.cliente_id
                 JOIN veiculo v ON v.id = o.veiculo_id
                 JOIN servico s ON s.id = o.servico_id
                 WHERE o.id = ?',
];

$tipo = $_GET['tipo'] ?? '';
$id = $_GET['id'] ?? '';
if (!isset($consultas[$tipo]) || !ctype_digit($id)) responder(false, ['Requisição inválida.'], 400);

$registro = buscar($consultas[$tipo], [(int) $id]);
if (!$registro) responder(false, ['Registro não encontrado.'], 404);

if ($tipo === 'ordens') {
    $registro['pecas'] = executar(
        "SELECT i.peca_id AS id, i.quantidade, p.codigo || ' - ' || p.descricao AS texto
         FROM ordem_servico_peca i JOIN peca p ON p.id = i.peca_id
         WHERE i.ordem_servico_id = ? ORDER BY p.descricao",
        [(int) $id]
    )->fetchAll();
}

responder(true, $registro);
