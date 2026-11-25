<?php
// Lista registros do banco para as tabelas das páginas e para os <select>.
// Uso: php/listar.php?tipo=clientes
require __DIR__ . '/funcoes.php';
exigirLogin();

// Somente consultas pré-definidas (o nome da tabela nunca vem do usuário).
// No PostgreSQL os apelidos vão entre aspas duplas para manter maiúsculas e acentos.
$consultas = [
    'clientes' => 'SELECT nome AS "Nome", cpf AS "CPF", telefone AS "Telefone", cidade AS "Cidade"
                   FROM cliente ORDER BY id DESC LIMIT 20',
    'veiculos' => 'SELECT v.placa AS "Placa", v.marca || \' \' || v.modelo AS "Veículo", v.ano AS "Ano",
                          v.quilometragem AS "Km", c.nome AS "Proprietário"
                   FROM veiculo v JOIN cliente c ON c.id = v.cliente_id ORDER BY v.id DESC LIMIT 20',
    'mecanicos' => 'SELECT nome AS "Nome", especialidade AS "Especialidade", valor_hora AS "Valor hora",
                           TO_CHAR(data_admissao, \'DD/MM/YYYY\') AS "Admissão"
                    FROM mecanico ORDER BY id DESC LIMIT 20',
    'pecas' => 'SELECT codigo AS "Código", descricao AS "Descrição", quantidade AS "Qtd",
                       estoque_minimo AS "Mínimo", ROUND(preco_custo * (1 + margem / 100), 2) AS "Preço venda"
                FROM peca ORDER BY id DESC LIMIT 20',
    'ordens' => 'SELECT o.id AS "OS", c.nome AS "Cliente", v.placa AS "Placa", m.nome AS "Mecânico", s.nome AS "Serviço",
                        TO_CHAR(o.data_previsao, \'DD/MM/YYYY\') AS "Previsão", o.total AS "Total"
                 FROM ordem_servico o
                 JOIN cliente c ON c.id = o.cliente_id
                 JOIN veiculo v ON v.id = o.veiculo_id
                 JOIN mecanico m ON m.id = o.mecanico_id
                 JOIN servico s ON s.id = o.servico_id
                 ORDER BY o.id DESC LIMIT 20',
    // Opções para <select>: sempre retornam "valor" e "texto".
    'opcoes-mecanicos' => 'SELECT id AS valor, nome || \' (\' || especialidade || \')\' AS texto FROM mecanico ORDER BY nome',
    'opcoes-servicos' => 'SELECT codigo AS valor, nome || \' (R$ \' || REPLACE(valor_hora::text, \'.\', \',\') || \'/h)\' AS texto
                          FROM servico ORDER BY valor_hora',
];

$tipo = $_GET['tipo'] ?? '';
if (!isset($consultas[$tipo])) responder(false, ['Tipo de listagem inválido.'], 400);

responder(true, conexao()->query($consultas[$tipo])->fetchAll());
