<?php
/**
 * 메일 발송.
 * - 운영: PHP mail() (카페24 발송 가능 여부는 배포 후 확인 필요)
 * - 로컬(debug): 실제로 보내지 않고 app/storage/mail.log 에 내용을 남김 (app/ 은 웹 접근 차단)
 */

function send_mail(string $to, string $subject, string $body): bool
{
    if (app_config()['debug']) {
        $dir = __DIR__ . '/storage';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }
        $entry = sprintf("==== %s\nTo: %s\nSubject: %s\n\n%s\n\n", date('Y-m-d H:i:s'), $to, $subject, $body);
        return file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX) !== false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . (app_config()['mail_from'] ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')),
    ];
    return mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, implode("\r\n", $headers));
}
