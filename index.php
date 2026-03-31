<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/includes/functions.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

$errors = [];
$successMessage = '';
$generalError = '';
$old = [
    'name' => '',
    'email' => '',
    'password' => '',
    'message' => '',
];

if (!isset($_SESSION['csrf_token'])) {
    generate_csrf_token();
}

try {
    $pdo = app_db();
} catch (RuntimeException $e) {
    $generalError = $e->getMessage();
    $pdo = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = (string) ($_POST['name'] ?? '');
    $old['email'] = (string) ($_POST['email'] ?? '');
    $old['password'] = (string) ($_POST['password'] ?? '');
    $old['message'] = (string) ($_POST['message'] ?? '');

    if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors['csrf'] = 'A sessão de segurança expirou ou é inválida. Atualize a página e tente novamente.';
    }

    [$nameOk, $nameValue] = validate_name($old['name']);
    if (!$nameOk) {
        $errors['name'] = $nameValue;
    }

    [$emailOk, $emailValue] = validate_email($old['email']);
    if (!$emailOk) {
        $errors['email'] = $emailValue;
    }

    [$passwordOk, $passwordValue] = validate_password($old['password']);
    if (!$passwordOk) {
        $errors['password'] = $passwordValue;
    }

    [$messageOk, $messageValue] = validate_message($old['message']);
    if (!$messageOk) {
        $errors['message'] = $messageValue;
    }

    if (empty($errors) && $pdo instanceof PDO) {
        try {
            $passwordHash = password_hash($passwordValue, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException('Não foi possível processar a senha com segurança.');
            }

            [$cipherText, $iv] = encrypt_message($messageValue);

            $stmt = $pdo->prepare(
                'INSERT INTO submissions (name, email, password_hash, message_ciphertext, message_iv)
                 VALUES (:name, :email, :password_hash, :message_ciphertext, :message_iv)'
            );

            $stmt->execute([
                ':name' => $nameValue,
                ':email' => $emailValue,
                ':password_hash' => $passwordHash,
                ':message_ciphertext' => $cipherText,
                ':message_iv' => $iv,
            ]);

            $successMessage = 'Cadastro enviado com sucesso. Os dados foram validados, armazenados e registrados no histórico.';
            $old = ['name' => '', 'email' => '', 'password' => '', 'message' => ''];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $generalError = 'Erro ao salvar no banco de dados: ' . $e->getMessage();
        }
    }
}

$history = [];
if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->query('SELECT id, name, email, message_ciphertext, message_iv, created_at FROM submissions ORDER BY created_at DESC, id DESC');
        $history = $stmt ? $stmt->fetchAll() : [];
    } catch (Throwable $e) {
        $generalError = $generalError !== ''
            ? $generalError . ' | Erro ao carregar histórico: ' . $e->getMessage()
            : 'Erro ao carregar histórico: ' . $e->getMessage();
    }
}

$total = is_array($history) ? count($history) : 0;
$errorList = create_error_summary($errors);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema Seguro de Cadastro e Histórico</title>
  <meta name="description" content="Sistema seguro para cadastro com validação dupla, armazenamento protegido e histórico em tempo real.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Canvas background layers -->
  <div class="canvas" aria-hidden="true">
    <div class="canvas-gradient"></div>
    <div class="canvas-scan"></div>
    <div class="canvas-orbs"></div>
  </div>

  <!-- Loading overlay -->
  <div class="loader-overlay" id="loaderOverlay" aria-hidden="true">
    <div class="loader-box">
      <div class="spinner" aria-hidden="true"></div>
      <strong>Processando</strong>
      <p>Validando e protegendo os dados</p>
    </div>
  </div>

  <div class="shell fade-in">

    <!-- ── TOPBAR ─────────────────────────────────────────────────── -->
    <header class="topbar">
      <div class="brand">
        <div class="brand-mark"><i class="fa-solid fa-shield-halved"></i></div>
        <div>
          <h1>Sistema Seguro</h1>
          <p>Validação, criptografia e histórico centralizado</p>
        </div>
      </div>
      <div class="badge">
        <div class="status-dot"></div>
        <i class="fa-solid fa-database"></i>
        Banco ativo e histórico integrado
      </div>
    </header>

    <!-- ── HERO ───────────────────────────────────────────────────── -->
    <main class="hero">

      <!-- Left: copy + metrics -->
      <section class="hero-copy">
        <div class="kicker">
          <i class="fa-solid fa-triangle-exclamation"></i>
          Projeto sério, organizado e direto
        </div>

        <h2>Cadastro <span>seguro</span> com rigor de validação.</h2>

        <p>
          Captura nome, e-mail, senha e mensagem com validação em duas camadas,
          grava no MySQL e exibe o histórico na mesma página.
          Senha armazenada com hash, mensagem criptografada antes de ir ao banco.
        </p>

        <div class="metrics">
          <div class="metric">
            <strong><i class="fa-solid fa-user-shield"></i> Front e back</strong>
            <span>Mesmas regras de validação em duas camadas independentes.</span>
          </div>
          <div class="metric">
            <strong><i class="fa-solid fa-lock"></i> Proteção</strong>
            <span>Prepared statements, CSRF token e tratamento de erros.</span>
          </div>
          <div class="metric">
            <strong><i class="fa-solid fa-clock-rotate-left"></i> Histórico</strong>
            <span>Registros em ordem reversa, mensagens descriptografadas.</span>
          </div>
        </div>
      </section>

      <!-- Right: form panel -->
      <section class="panel">
        <div class="panel-header">
          <div>
            <h3><i class="fa-solid fa-pen-to-square"></i> Cadastro de envio</h3>
            <p>Preencha todos os campos para registrar a submissão.</p>
          </div>
        </div>

        <?php if ($generalError !== ''): ?>
          <div class="global-error">
            <strong><i class="fa-solid fa-circle-xmark"></i> Erro detectado</strong>
            <div><?= e($generalError) ?></div>
          </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
          <div class="global-success">
            <strong><i class="fa-solid fa-circle-check"></i> Operação concluída</strong>
            <div><?= e($successMessage) ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorList)): ?>
          <div class="global-error">
            <strong><i class="fa-solid fa-bell"></i> Corrija os erros abaixo</strong>
            <ul>
              <?php foreach ($errorList as $item): ?>
                <li><strong><?= e($item['field']) ?>:</strong> <?= e($item['message']) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form id="secureForm" class="form-grid" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

          <!-- Name -->
          <div class="field">
            <label for="name">
              <i class="fa-solid fa-user"></i>
              Nome completo
            </label>
            <input
              class="control"
              type="text"
              id="name"
              name="name"
              value="<?= e($old['name']) ?>"
              autocomplete="name"
              maxlength="100"
              placeholder="Digite seu nome completo"
              data-validate="true"
              aria-describedby="nameHelp nameError"
              <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>
            >
            <div class="help" id="nameHelp">
              <span>Apenas letras, espaços, hífen e apóstrofo</span>
              <span>2 – 100 caracteres</span>
            </div>
            <div class="error" id="nameError" data-error-for="name"><?= e($errors['name'] ?? '') ?></div>
          </div>

          <!-- Email -->
          <div class="field">
            <label for="email">
              <i class="fa-solid fa-envelope"></i>
              E-mail
            </label>
            <input
              class="control"
              type="email"
              id="email"
              name="email"
              value="<?= e($old['email']) ?>"
              autocomplete="email"
              maxlength="255"
              placeholder="nome@dominio.com"
              data-validate="true"
              aria-describedby="emailHelp emailError"
              <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>
            >
            <div class="help" id="emailHelp">
              <span>Formato de e-mail obrigatório</span>
              <span>Até 255 caracteres</span>
            </div>
            <div class="error" id="emailError" data-error-for="email"><?= e($errors['email'] ?? '') ?></div>
          </div>

          <!-- Password -->
          <div class="field">
            <label for="password">
              <i class="fa-solid fa-key"></i>
              Senha
            </label>
            <input
              class="control"
              type="password"
              id="password"
              name="password"
              value=""
              autocomplete="new-password"
              minlength="8"
              maxlength="64"
              placeholder="Crie uma senha forte"
              data-validate="true"
              aria-describedby="passwordHelp passwordError passwordRules"
              <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>
            >

            <!-- Password requirements panel (shown on focus) -->
            <div class="pw-rules" id="passwordRules" aria-live="polite" role="status">
              <p class="pw-rules-title">
                <i class="fa-solid fa-shield-halved"></i>
                Requisitos da senha
              </p>
              <ul class="pw-rules-list">
                <li class="pw-rule" id="rule-length">
                  <i class="fa-solid fa-circle-xmark icon-x"></i>
                  <i class="fa-solid fa-circle-check icon-ok"></i>
                  Mínimo 8 caracteres
                </li>
                <li class="pw-rule" id="rule-lower">
                  <i class="fa-solid fa-circle-xmark icon-x"></i>
                  <i class="fa-solid fa-circle-check icon-ok"></i>
                  Letra minúscula (a–z)
                </li>
                <li class="pw-rule" id="rule-upper">
                  <i class="fa-solid fa-circle-xmark icon-x"></i>
                  <i class="fa-solid fa-circle-check icon-ok"></i>
                  Letra maiúscula (A–Z)
                </li>
                <li class="pw-rule" id="rule-digit">
                  <i class="fa-solid fa-circle-xmark icon-x"></i>
                  <i class="fa-solid fa-circle-check icon-ok"></i>
                  Número (0–9)
                </li>
                <li class="pw-rule" id="rule-special">
                  <i class="fa-solid fa-circle-xmark icon-x"></i>
                  <i class="fa-solid fa-circle-check icon-ok"></i>
                  Caractere especial (!&#64;#$%&amp;...)
                </li>
              </ul>
            </div>

            <div class="help" id="passwordHelp">
              <span>Clique no campo para ver os requisitos</span>
              <span>8 – 64 caracteres</span>
            </div>
            <div class="error" id="passwordError" data-error-for="password"><?= e($errors['password'] ?? '') ?></div>
          </div>

          <!-- Message -->
          <div class="field">
            <label for="message">
              <i class="fa-solid fa-message"></i>
              Mensagem
            </label>
            <textarea
              class="control"
              id="message"
              name="message"
              maxlength="250"
              placeholder="Digite sua mensagem com até 250 caracteres"
              data-validate="true"
              aria-describedby="messageHelp messageError"
              <?= isset($errors['message']) ? 'aria-invalid="true"' : '' ?>
            ><?= e($old['message']) ?></textarea>
            <div class="help" id="messageHelp">
              <span>Texto livre</span>
              <span id="messageCounter">0/250 caracteres</span>
            </div>
            <div class="error" id="messageError" data-error-for="message"><?= e($errors['message'] ?? '') ?></div>
          </div>

          <!-- Actions -->
          <div class="actions">
            <button class="btn" type="submit">
              <span class="shine"></span>
              <i class="fa-solid fa-paper-plane"></i>
              Enviar e registrar
            </button>
            <a class="ghost" href="#historico">
              <i class="fa-solid fa-clock-rotate-left"></i>
              Ver histórico
            </a>
          </div>
        </form>
      </section>
    </main>

    <!-- ── HISTORY SECTION ────────────────────────────────────────── -->
    <section class="section" id="historico">
      <div class="section-head">
        <div class="section-title">
          <h3><i class="fa-solid fa-folder-open"></i> Histórico de mensagens</h3>
          <p>Registros armazenados no MySQL, mensagens descriptografadas em tempo real.</p>
        </div>
        <div class="history-count">
          <i class="fa-solid fa-layer-group"></i>
          <?= $total ?> registro<?= $total !== 1 ? 's' : '' ?>
        </div>
      </div>

      <?php if (empty($history)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-inbox"></i>
          <p>Nenhum registro foi enviado até o momento.</p>
        </div>
      <?php else: ?>
        <div class="history-list">
          <?php foreach ($history as $item): ?>
            <?php $plainMessage = decrypt_message((string) $item['message_ciphertext'], (string) $item['message_iv']); ?>
            <article class="record">
              <div class="record-top">
                <div>
                  <h4><i class="fa-solid fa-id-card"></i> <?= e((string) $item['name']) ?></h4>
                  <div class="meta">
                    <span><i class="fa-solid fa-envelope"></i> <?= e((string) $item['email']) ?></span>
                    <span><i class="fa-solid fa-calendar-days"></i> <?= e((string) $item['created_at']) ?></span>
                  </div>
                </div>
                <div class="record-id">
                  <i class="fa-solid fa-hashtag"></i> <?= (int) $item['id'] ?>
                </div>
              </div>
              <div class="message-box"><?= e($plainMessage) ?></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- ── FOOTER ─────────────────────────────────────────────────── -->
    <footer class="footer">
      <small>© <?= date('Y') ?> Sistema Seguro de Cadastro</small>
      <small>Desenvolvido por João Alves e Fabio</small>
    </footer>

  </div><!-- /.shell -->

  <!-- Floating credit bubble -->
  <div class="floating-bubble" aria-label="Créditos do projeto">
    <i class="fa-solid fa-user-pen"></i>
    <strong>João Alves &amp; Fabio</strong>
  </div>

  <script src="assets/js/app.js"></script>
</body>
</html>
