# Cadastro

> Sistema seguro de registro e histórico — PHP · MySQL · PDO

---

## Visão Geral

Aplicação web para captura, validação e armazenamento seguro de registros. O histórico é exibido na mesma página do formulário, sem redirecionamentos.

**Dados coletados:** Nome · E-mail · Senha · Mensagem

---

## Estrutura

```
cadastro/
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
└── index.php
```

---

## Requisitos

| Dependência | Versão mínima |
|-------------|---------------|
| PHP         | 8.0           |
| MySQL       | 5.7 / MariaDB compatível com `utf8mb4` |
| XAMPP       | qualquer versão estável |

---

## Instalação

**1. Copie o projeto**

```
C:\xampp\htdocs\cadastro\
```

**2. Inicie o XAMPP**

Ative os módulos **Apache** e **MySQL** no painel de controle.

**3. Importe o banco de dados**

Abra o MySQL Workbench e execute:

```
sql/schema.sql
```

Isso cria o banco `secure_contact_system` e a tabela `submissions`.

**4. Revise a configuração**

Abra `app/config/database.php` e confirme as credenciais:

```php
'host'     => 'localhost',
'dbname'   => 'secure_contact_system',
'user'     => 'root',
'password' => '',
```

**5. Acesse no navegador**

```
http://localhost/cadastro/index.php
```

---

## Segurança

| Camada        | Recurso aplicado                                  |
|---------------|---------------------------------------------------|
| Banco de dados| PDO com `prepare()` e `execute()` — sem SQL Injection |
| Senha         | `password_hash()` com BCRYPT                      |
| Mensagem      | Criptografia simétrica antes de salvar            |
| Saída HTML    | `htmlspecialchars()` em todos os dados exibidos   |
| Formulário    | Token CSRF por sessão                             |
| Validação     | Front-end (JS) e back-end (PHP) — obrigatória nas duas camadas |

---

## Regras de Validação

### Nome
- Obrigatório · 2–100 caracteres
- Apenas letras, espaços, hífen e apóstrofo

### E-mail
- Obrigatório · formato válido · máximo de 255 caracteres

### Senha
- Obrigatório · 8–64 caracteres
- Deve conter: letra minúscula · letra maiúscula · número · caractere especial

### Mensagem
- Obrigatório · 3–250 caracteres.

---

## Schema do Banco

```sql
CREATE TABLE submissions (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name              VARCHAR(100)  NOT NULL,
  email             VARCHAR(255)  NOT NULL,
  password_hash     VARCHAR(255)  NOT NULL,
  message_ciphertext TEXT         NOT NULL,
  message_iv        VARCHAR(64)   NOT NULL,
  created_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);
```

---

## Git

```bash
git init
git add .
git commit -m "versão inicial"
```

---

## Observações

- A chave de criptografia em `functions.php` deve ser substituída por uma string longa e aleatória antes de qualquer uso fora de ambiente local.
- Projeto desenvolvido para fins acadêmicos.

---

*Desenvolvido por João Alves e Fabio*
