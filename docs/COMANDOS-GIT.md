# Comandos git (na ordem de execução)

Troque `USUARIO` pelo usuário do GitHub dono do repositório.

## 1. Enzo — cria o repositório e a branch main
```bash
git init                                   # inicia o repositório local
git branch -M main                         # renomeia a branch inicial para main
git add README.md                          # adiciona o README à área de preparação
git commit -m "Cria README com descrição e modelo de dados"   # primeiro commit na main
git remote add origin https://github.com/USUARIO/oficina-tpi2.git  # liga ao repositório remoto
git push -u origin main                    # envia a main e define o upstream
```
## 2. Enzo — branch-aluno-1 (base + clientes + veículos)
```bash
git checkout -b branch-aluno-1             # cria a branch do aluno 1 e muda para ela
git add index.html css/ js/ php/funcoes.php           # base do sistema
git commit -m "Página inicial, menu, CSS, envio via fetch e funções PHP comuns"
git add clientes.html php/cliente.php veiculos.html php/veiculo.php
git commit -m "Formulários de cliente e veículo com validação no PHP"
git push -u origin branch-aluno-1          # envia a branch ao GitHub
git checkout main                          # volta para a main
git merge branch-aluno-1                   # incorpora o trabalho do aluno 1
git push origin main                       # atualiza a main remota
```
## 3. Higor — branch-aluno-2 (mecânicos + peças + ordens)
```bash
git clone https://github.com/USUARIO/oficina-tpi2.git  # baixa o repositório
cd oficina-tpi2
git checkout -b branch-aluno-2             # cria a branch do aluno 2
git add mecanicos.html php/mecanico.php pecas.html php/peca.php
git commit -m "Formulários de mecânico e peça com validação no PHP"
git add ordens.html php/ordem.php docs/
git commit -m "Formulário de ordem de serviço com orçamento e questionários"
git push -u origin branch-aluno-2          # envia a branch ao GitHub
git checkout main
git pull origin main                       # garante a main atualizada
git merge branch-aluno-2                   # incorpora o trabalho do aluno 2
git push origin main                       # main final com todas as páginas
```
## 4. Conferência
```bash
git log --oneline --graph --all            # mostra o histórico e os merges
git branch -a                              # lista branches locais e remotas
```
