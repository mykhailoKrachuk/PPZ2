<?php
declare(strict_types=1);

// Устанавливаем заголовок JSON ДО проверки авторизации
header('Content-Type: application/json');

require __DIR__ . '/config.php';
require __DIR__ . '/require_auth.php';
requireRole('deliver');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$parcel_number = $_POST['parcel_number'] ?? '';

if (empty($parcel_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Numer przesyłki jest wymagany']);
    exit;
}

try {
    // Проверяем, что посылка существует, отправлена, но еще не получена
    // Также получаем user_id для отправки email
    $stmt = $db->prepare("
        SELECT p.id, p.parcel_number, p.sent_at, p.received_at, p.user_id, p.sender_name
        FROM parcel p
        WHERE p.parcel_number = :number
        AND p.sent_at IS NOT NULL
        AND p.received_at IS NULL
        LIMIT 1
    ");
    
    $stmt->execute([':number' => $parcel_number]);
    $parcel = $stmt->fetch();
    
    if (!$parcel) {
        http_response_code(404);
        echo json_encode(['error' => 'Nie znaleziono przesyłki lub przesyłka została już otrzymana']);
        exit;
    }
    
    // Генерируем 6-значный код
    $code = str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Обновляем: ставим received_at, меняем статус на "received" и записываем код
    // Проверяем, существует ли поле code в таблице
    try {
        $updateStmt = $db->prepare("
            UPDATE parcel
            SET received_at = NOW(),
                status = 'received',
                code = :code
            WHERE id = :id
        ");
        
        $updateStmt->execute([
            ':id' => $parcel['id'],
            ':code' => $code
        ]);
    } catch (PDOException $e) {
        // Если поле code не существует, обновляем без него
        if (strpos($e->getMessage(), 'code') !== false) {
            $updateStmt = $db->prepare("
                UPDATE parcel
                SET received_at = NOW(),
                    status = 'received'
                WHERE id = :id
            ");
            
            $updateStmt->execute([
                ':id' => $parcel['id']
            ]);
        } else {
            throw $e;
        }
    }
    
    // Проверяем, что обновление прошло успешно
    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Nie udało się zaktualizować przesyłki');
    }
    
    // Отправляем промокод на email, если есть user_id
    $emailSent = false;
    if (!empty($parcel['user_id'])) {
        $userStmt = $db->prepare("SELECT mail, name FROM users WHERE id = :user_id LIMIT 1");
        $userStmt->execute([':user_id' => $parcel['user_id']]);
        $user = $userStmt->fetch();
        
        if ($user && !empty($user['mail'])) {
            $emailSent = sendCodeEmail($user['mail'], $user['name'] ?? $parcel['sender_name'], $parcel_number, $code);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Przesyłka została otrzymana',
        'parcel_number' => $parcel_number,
        'code' => $code,
        'email_sent' => $emailSent
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Parcel receive error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
}

/**
 * Отправляет промокод на email пользователю через SMTP
 */
function sendCodeEmail(string $email, string $name, string $parcelNumber, string $code): bool {
    // Проверяем, включена ли отправка email
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        error_log("Email sending is disabled in config");
        return false;
    }
    
    $subject = 'Kod odbioru przesyłki - Salfetka';
    
    $htmlMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9fafb; padding: 30px; border-radius: 0 0 8px 8px; }
            .code-box { background: white; border: 2px solid #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .code { font-size: 32px; font-weight: bold; color: #667eea; letter-spacing: 4px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>📦 Salfetka</h1>
            </div>
            <div class='content'>
                <p>Witaj <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Twoja przesyłka <strong>" . htmlspecialchars($parcelNumber) . "</strong> została otrzymana i jest gotowa do odbioru.</p>
                <p>Aby odebrać przesyłkę, podaj poniższy kod:</p>
                <div class='code-box'>
                    <div class='code'>" . htmlspecialchars($code) . "</div>
                </div>
                <p>Kod jest ważny do momentu odbioru przesyłki.</p>
                <p>Pozdrawiamy,<br>Zespół Salfetka</p>
            </div>
            <div class='footer'>
                <p>To jest automatyczna wiadomość. Prosimy nie odpowiadać na ten email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Если настроен SMTP, используем его, иначе fallback на mail()
    if (defined('SMTP_HOST') && !empty(SMTP_HOST) && defined('SMTP_USER') && !empty(SMTP_USER)) {
        return sendViaSMTP($email, $subject, $htmlMessage);
    } else {
        // Fallback на стандартную функцию mail()
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@salfetka.pl';
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Salfetka';
        $headers .= "From: {$fromName} <{$fromEmail}>" . "\r\n";
        $headers .= "Reply-To: {$fromEmail}" . "\r\n";
        
        try {
            $result = mail($email, $subject, $htmlMessage, $headers);
            if (!$result) {
                error_log("Failed to send email to {$email} using mail()");
            }
            return $result;
        } catch (Throwable $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Отправка email через SMTP (простая реализация без внешних библиотек)
 */
function sendViaSMTP(string $to, string $subject, string $htmlBody): bool {
    try {
        $host = SMTP_HOST;
        $port = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $user;
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Salfetka';
        
        // Подключаемся к SMTP серверу
        $socket = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP connection failed: {$errstr} ({$errno})");
            return false;
        }
        
        // Читаем приветствие сервера
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '220') {
            fclose($socket);
            error_log("SMTP server error: {$response}");
            return false;
        }
        
        // EHLO
        fputs($socket, "EHLO {$host}\r\n");
        $response = fgets($socket, 515);
        
        // STARTTLS для порта 587
        if ($port == 587) {
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 515);
            if (substr($response, 0, 3) === '220') {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO {$host}\r\n");
                $response = fgets($socket, 515);
            }
        }
        
        // Авторизация
        fputs($socket, "AUTH LOGIN\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($user) . "\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, base64_encode($pass) . "\r\n");
        $response = fgets($socket, 515);
        if (substr($response, 0, 3) !== '235') {
            fclose($socket);
            error_log("SMTP authentication failed: {$response}");
            return false;
        }
        
        // Отправка письма
        fputs($socket, "MAIL FROM: <{$fromEmail}>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "RCPT TO: <{$to}>\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "DATA\r\n");
        $response = fgets($socket, 515);
        
        // Заголовки письма
        $headers = "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";
        
        $body = chunk_split(base64_encode($htmlBody));
        
        fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $response = fgets($socket, 515);
        
        fputs($socket, "QUIT\r\n");
        fclose($socket);
        
        if (substr($response, 0, 3) === '250') {
            return true;
        } else {
            error_log("SMTP send failed: {$response}");
            return false;
        }
        
    } catch (Throwable $e) {
        error_log("SMTP error: " . $e->getMessage());
        return false;
    }
}

