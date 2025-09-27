<?php
/**
 * API bootstrap ve yardımcı fonksiyonlar.
 */
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=UTF-8');

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Geçersiz JSON verisi.']);
        exit;
    }
    return $data;
}

function send_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json(['ok' => false, 'error' => 'Bu uç sadece POST isteklerini kabul eder.'], 405);
    }
}

function ensure_get(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        send_json(['ok' => false, 'error' => 'Bu uç sadece GET isteklerini kabul eder.'], 405);
    }
}

function verify_csrf(?array $body = null): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        send_json(['ok' => false, 'error' => 'Oturum doğrulaması başarısız.'], 419);
    }
}

function sanitize_string(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    return trim(filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
}

function begin_transaction(PDO $pdo): void
{
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }
}

function commit_transaction(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
}

function rollback_transaction(PDO $pdo): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
