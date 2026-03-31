# Sistema Seguro de Cadastro e Histórico

Projeto PHP + MySQL com validação no front-end e no back-end, armazenamento seguro e exibição de histórico na mesma página.

## Objetivo

Capturar, validar e armazenar os seguintes dados:

- Nome
- E-mail
- Senha
- Mensagem

Regras principais:

- validação completa no front-end
- validação completa no back-end em PHP
- uso de `input type="email"` e `input type="password"`
- mensagem com limite de 250 caracteres
- proteção contra SQL Injection com `PDO` e prepared statements
- senha armazenada com `password_hash`
- mensagem armazenada criptografada
- histórico carregado no mesmo formulário
- tratamento explícito de erros
- organização por pastas

## Estrutura do projeto

```text
secure_contact_project/
├── app/
│   ├── config/
│   │   └── database.php
│   └── includes/
│       └── functions.php
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
├── sql/
│   └── schema.sql
├── index.php
└── README.md
```

## Requisitos

- Windows
- XAMPP
- MySQL Workbench
- PHP 8 ou superior
- MySQL/MariaDB compatível com `utf8mb4`

## Instalação no Windows com XAMPP

1. Copie a pasta do projeto para `C:\xampp\htdocs\secure_contact_project`.
2. Abra o XAMPP Control Panel e inicie `Apache` e `MySQL`.
3. Abra o MySQL Workbench.
4. Importe ou execute o arquivo `sql/schema.sql`.
5. Confirme a criação do banco `secure_contact_system` e da tabela `submissions`.
6. Revise o arquivo `app/config/database.php` e, se necessário, ajuste usuário, senha ou nome do banco.
7. Abra no navegador:
   `http://localhost/secure_contact_project/index.php`

## Configuração do banco

O arquivo `sql/schema.sql` cria:

- banco de dados `secure_contact_system`
- tabela `submissions`

Campos principais:

- `name`
- `email`
- `password_hash`
- `message_ciphertext`
- `message_iv`
- `created_at`

## Segurança aplicada

- `PDO` com `prepare` e `execute`
- `htmlspecialchars` na saída
- token `CSRF`
- `password_hash` para a senha
- criptografia da mensagem antes de salvar
- validação forte de nome, e-mail, senha e mensagem
- bloqueio de carga útil inválida no front e no back

## Regras de validação

### Nome

- obrigatório
- mínimo de 2 caracteres
- máximo de 100 caracteres
- apenas letras, espaços, hífen e apóstrofo

### E-mail

- obrigatório
- formato de e-mail válido
- máximo de 255 caracteres

### Senha

- obrigatório
- mínimo de 8 caracteres
- máximo de 64 caracteres
- ao menos uma letra minúscula
- ao menos uma letra maiúscula
- ao menos um número
- ao menos um caractere especial

### Mensagem

- obrigatório
- mínimo de 3 caracteres
- máximo de 250 caracteres

## Execução

1. Abra o XAMPP.
2. Inicie Apache e MySQL.
3. Verifique se o banco foi importado.
4. Acesse a página principal.
5. Envie um registro válido.
6. O histórico aparecerá abaixo do formulário na mesma página.

## Organização para Git

Estrutura recomendada de commit:

```bash
git init
git add .
git commit -m "Versão inicial do sistema seguro"
```

## Observações

- O projeto foi pensado para execução local em ambiente acadêmico.
- Caso o MySQL esteja com usuário ou senha diferentes, ajuste o arquivo `app/config/database.php`.
- A chave de criptografia deve ser substituída por uma string longa e aleatória antes de uso fora do ambiente de testes.

## Créditos

Desenvolvido por João Alves e Fabio.
