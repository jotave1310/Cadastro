<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('A sessão precisa estar ativa para gerar o token CSRF.');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function normalize_name(string $name): string
{
    $name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';
    return $name;
}

function validate_name(string $name): array
{
    $name = normalize_name($name);
    $length = mb_strlen($name, 'UTF-8');

    if ($name === '') {
        return [false, 'O nome é obrigatório.'];
    }

    if ($length < 2) {
        return [false, 'O nome deve ter no mínimo 2 caracteres.'];
    }

    if ($length > 100) {
        return [false, 'O nome deve ter no máximo 100 caracteres.'];
    }

    if (!preg_match('/^[\p{L}][\p{L}\p{M}\s\'\-]*[\p{L}]$/u', $name)) {
        return [false, 'O nome deve conter apenas letras, espaços, hífen ou apóstrofo.'];
    }

    return [true, $name];
}

function validate_email(string $email): array
{
    $email = trim($email);

    if ($email === '') {
        return [false, 'O e-mail é obrigatório.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'O e-mail informado não possui um formato válido.'];
    }

    if (mb_strlen($email, 'UTF-8') > 255) {
        return [false, 'O e-mail deve ter no máximo 255 caracteres.'];
    }

    return [true, strtolower($email)];
}

function validate_password(string $password): array
{
    $length = strlen($password);

    if ($password === '') {
        return [false, 'A senha é obrigatória.'];
    }

    if ($length < 8) {
        return [false, 'A senha deve ter no mínimo 8 caracteres.'];
    }

    if ($length > 64) {
        return [false, 'A senha deve ter no máximo 64 caracteres.'];
    }

    if (!preg_match('/[a-z]/', $password)) {
        return [false, 'A senha deve conter ao menos uma letra minúscula.'];
    }

    if (!preg_match('/[A-Z]/', $password)) {
        return [false, 'A senha deve conter ao menos uma letra maiúscula.'];
    }

    if (!preg_match('/\d/', $password)) {
        return [false, 'A senha deve conter ao menos um número.'];
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        return [false, 'A senha deve conter ao menos um caractere especial.'];
    }

    return [true, $password];
}

function validate_message(string $message): array
{
    $message = trim($message);
    $length = mb_strlen($message, 'UTF-8');

    if ($message === '') {
        return [false, 'A mensagem é obrigatória.'];
    }

    if ($length > 250) {
        return [false, 'A mensagem deve ter no máximo 250 caracteres.'];
    }

    if ($length < 3) {
        return [false, 'A mensagem deve ter no mínimo 3 caracteres.'];
    }

    return [true, $message];
}

function secret_key(): string
{
    return hash('sha256', APP_SECRET_KEY, true);
}

function encrypt_message(string $plainText): array
{
    $iv = random_bytes(openssl_cipher_iv_length('AES-256-CBC'));
    $cipherText = openssl_encrypt(
        $plainText,
        'AES-256-CBC',
        secret_key(),
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($cipherText === false) {
        throw new RuntimeException('Não foi possível criptografar a mensagem.');
    }

    return [base64_encode($cipherText), base64_encode($iv)];
}

function decrypt_message(string $cipherTextBase64, string $ivBase64): string
{
    $cipherText = base64_decode($cipherTextBase64, true);
    $iv = base64_decode($ivBase64, true);

    if ($cipherText === false || $iv === false) {
        return '[mensagem indisponível]';
    }

    $plainText = openssl_decrypt(
        $cipherText,
        'AES-256-CBC',
        secret_key(),
        OPENSSL_RAW_DATA,
        $iv
    );

    return $plainText === false ? '[mensagem indisponível]' : $plainText;
}

function create_error_summary(array $errors): array
{
    $summary = [];

    foreach ($errors as $field => $message) {
        $summary[] = [
            'field' => $field,
            'message' => $message,
        ];
    }

    return $summary;
}
