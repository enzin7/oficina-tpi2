# Oficina Torque Certo — Sistema Administrativo (TPI II · Momento I)

Pequeno sistema administrativo para uma oficina mecânica.

**Grupo**
| Aluno | RA | Usuário GitHub | Branch |
|---|---|---|---|
| Enzo Mota Torquette | 5175090 | `USUARIO_ENZO` | `branch-aluno-1` |
| Higor Costa Valle Marquez Chagas | 5174284 | `USUARIO_HIGOR` | `branch-aluno-2` |

## Como executar
```bash
php -S localhost:8000
```
Abra `http://localhost:8000` no navegador.

## Estrutura
```
index.html          página inicial com menu
clientes.html       formulário de cliente        -> php/cliente.php
veiculos.html       formulário de veículo        -> php/veiculo.php
mecanicos.html      formulário de mecânico       -> php/mecanico.php
pecas.html          formulário de peça           -> php/peca.php
ordens.html         formulário de ordem de serviço -> php/ordem.php
css/style.css       estilos
js/app.js           envio dos formulários via fetch (POST) e exibição da resposta
php/funcoes.php     funções comuns (validação, CPF, placa, resposta JSON)
docs/               questionários de desenvolvimento e comandos git
```
O HTML fica separado do PHP: o JavaScript envia os dados com `fetch` e o PHP responde apenas JSON.

## Modelo de dados planejado (7 entidades — banco ainda não criado)
| Tabela | Campos principais | Relacionamentos |
|---|---|---|
| `cliente` | id, nome, cpf, data_nascimento, telefone, email, cidade | 1:N veiculo, 1:N ordem_servico |
| `veiculo` | id, cliente_id, placa, marca, modelo, ano, quilometragem, combustivel | N:1 cliente |
| `mecanico` | id, nome, cpf, telefone, especialidade, valor_hora, data_admissao | 1:N ordem_servico |
| `peca` | id, codigo, descricao, fabricante, preco_custo, margem, quantidade, estoque_minimo | N:N ordem_servico (via ordem_servico_peca) |
| `servico` | id, nome, valor_hora | 1:N ordem_servico |
| `ordem_servico` | id, cliente_id, veiculo_id, mecanico_id, servico_id, data_entrada, data_previsao, horas, desconto, total, relato | N:1 cliente, veiculo, mecanico, servico |
| `ordem_servico_peca` | ordem_servico_id, peca_id, quantidade, preco_unitario | tabela associativa OS × peça |

## Funcionalidades e lógica adicional de cada formulário
| Formulário | Validações | Lógica adicional |
|---|---|---|
| Cliente | obrigatórios, CPF (dígitos verificadores), e-mail, telefone, data | calcula a idade e bloqueia menores de 18; padroniza nome; benefício para clientes de Uberlândia |
| Veículo | CPF do dono, placa antiga/Mercosul, ano 1950–próximo ano, km ≥ 0 | identifica padrão da placa, média de km/ano e próxima revisão (a cada 10.000 km) |
| Mecânico | CPF, especialidade da lista, valor/hora R$ 30–500, admissão não futura, telefone | tempo de casa, nível Júnior/Pleno/Sênior e estimativa mensal (176 h) |
| Peça | código, custo > 0, margem 0–300%, quantidades inteiras | preço de venda, lucro por unidade e alerta/sugestão de reposição |
| Ordem de Serviço | CPF, placa, serviço da tabela, datas coerentes, horas, peças, desconto ≤ 15% | orçamento (horas × valor/hora do serviço + peças − desconto) e prazo em dias |
