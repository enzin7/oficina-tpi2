# Oficina Torque Certo — Sistema Administrativo (TPI II)

Pequeno sistema administrativo para uma oficina mecânica.

**Grupo**
| Aluno | RA | Usuário GitHub | Branch |
|---|---|---|---|
| Enzo Mota Torquette | 5175090 | `enzin7` | `branch-aluno-1` |
| Higor Costa Valle Marquez Chagas | 5174284 | `HigorMarquezChagas` | `branch-aluno-2` |

## Como executar
1. Instale o PostgreSQL e habilite a extensão **pdo_pgsql** no PHP (no `php.ini`, tire o `;` de `extension=pdo_pgsql` — no XAMPP, `C:\xampp\php\php.ini` — e reinicie o Apache).
2. Crie o banco e as tabelas:
   ```bash
   psql -U postgres -c "CREATE DATABASE oficina ENCODING 'UTF8';"
   psql -U postgres -d oficina -f sql/banco.sql
   ```
   (ou pelo pgAdmin: crie o banco `oficina`, abra o Query Tool nele e execute `sql/banco.sql`)
3. Ajuste usuário/senha do Postgres em `php/conexao.php` (padrão: `postgres` / `postgres`).
4. Suba o servidor:
   ```bash
   php -S localhost:8000
   ```
5. Abra `http://localhost:8000/login.html` e entre com **admin / admin123**.

## Login e sessão
- `php/login.php` confere a senha com `password_verify` (senhas salvas com `password_hash` na tabela `usuario`), regenera o id da sessão e guarda o usuário em `$_SESSION`.
- Todo script PHP de cadastro/listagem chama `exigirLogin()` (`php/funcoes.php`): sem sessão responde 401. A sessão expira após 30 min sem uso.
- As páginas HTML usam `<body class="protegida">`: o `js/app.js` consulta `php/sessao.php` e redireciona para `login.html` se não houver sessão. O menu mostra o usuário logado e o botão **Sair** (`php/logout.php`).

## Estrutura
```
login.html          tela de login                -> php/login.php
index.html          página inicial com menu
clientes.html       formulário de cliente        -> php/cliente.php
veiculos.html       formulário de veículo        -> php/veiculo.php
mecanicos.html      formulário de mecânico       -> php/mecanico.php
pecas.html          formulário de peça           -> php/peca.php
ordens.html         formulário de ordem de serviço -> php/ordem.php
css/style.css       estilos
js/app.js           login, checagem de sessão, envio via fetch, listas e selects vindos do banco
php/funcoes.php     funções comuns (sessão, validação, CPF, placa, resposta JSON)
php/conexao.php     conexão PDO com o PostgreSQL e helpers inserir()/buscar()
php/sessao.php      informa se há usuário logado
php/logout.php      encerra a sessão
php/listar.php      últimos registros de cada tabela e opções dos <select>
sql/banco.sql       criação do banco, tabelas, serviços e usuário inicial
docs/               questionários de desenvolvimento e comandos git
```
O HTML fica separado do PHP: o JavaScript envia os dados com `fetch` e o PHP responde apenas JSON. Os dados são gravados no PostgreSQL com PDO e *prepared statements* (proteção contra SQL injection).

## Modelo de dados (`sql/banco.sql`)
| Tabela | Campos principais | Relacionamentos |
|---|---|---|
| `usuario` | id, nome, login, senha_hash | 1:N ordem_servico (quem abriu) |
| `cliente` | id, nome, cpf, data_nascimento, telefone, email, cidade | 1:N veiculo, 1:N ordem_servico |
| `veiculo` | id, cliente_id, placa, marca, modelo, ano, quilometragem, combustivel | N:1 cliente |
| `mecanico` | id, nome, cpf, telefone, especialidade, valor_hora, data_admissao | 1:N ordem_servico |
| `peca` | id, codigo, descricao, fabricante, preco_custo, margem, quantidade, estoque_minimo | N:N ordem_servico (via ordem_servico_peca) |
| `servico` | id, nome, valor_hora | 1:N ordem_servico |
| `ordem_servico` | id, cliente_id, veiculo_id, mecanico_id, servico_id, usuario_id, data_entrada, data_previsao, horas, valor_pecas, desconto, total, relato | N:1 cliente, veiculo, mecanico, servico, usuario |
| `ordem_servico_peca` | ordem_servico_id, peca_id, quantidade, preco_unitario | tabela associativa OS × peça |

## Funcionalidades e lógica adicional de cada formulário
| Formulário | Validações | Lógica adicional |
|---|---|---|
| Cliente | obrigatórios, CPF (dígitos verificadores), e-mail, telefone, data | calcula a idade e bloqueia menores de 18; padroniza nome; benefício para clientes de Uberlândia |
| Veículo | CPF do dono, placa antiga/Mercosul, ano 1950–próximo ano, km ≥ 0 | identifica padrão da placa, média de km/ano e próxima revisão (a cada 10.000 km) |
| Mecânico | CPF, especialidade da lista, valor/hora R$ 30–500, admissão não futura, telefone | tempo de casa, nível Júnior/Pleno/Sênior e estimativa mensal (176 h) |
| Peça | código, custo > 0, margem 0–300%, quantidades inteiras | preço de venda, lucro por unidade e alerta/sugestão de reposição |
| Ordem de Serviço | CPF, placa, serviço da tabela, datas coerentes, horas, peças, desconto ≤ 15% | orçamento (horas × valor/hora do serviço + peças − desconto) e prazo em dias |
