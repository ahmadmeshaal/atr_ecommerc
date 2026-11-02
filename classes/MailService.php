<?php

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config/mail.php';
    }

    public function sendOtp(string $email, string $otp): bool
    {
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        return $this->send($email, 'Your Ater verification code',
            '<p>Your verification code is:</p><p style="font-size:28px;font-weight:700;letter-spacing:8px">' . $safeOtp . '</p><p>This code expires in 5 minutes.</p>',
            "Your verification code is: {$otp}. It expires in 5 minutes.");
    }

    public function sendPasswordReset(string $email, string $resetLink): bool
    {
        $link = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');
        return $this->send($email, 'Reset your Ater password',
            '<p>We received a request to reset your password.</p><p><a href="' . $link . '" style="display:inline-block;background:#754b50;color:#fff;padding:14px 22px;text-decoration:none">Reset password</a></p><p>This link expires in 15 minutes. If you did not request this, you can ignore this email.</p>',
            "Reset your password using this link: {$resetLink}\nThis link expires in 15 minutes.");
    }

    public function sendOrderPlaced(array $order): bool
    {
        return $this->sendOrderUpdate($order, 'placed');
    }

    public function sendOrderPaid(array $order): bool
    {
        return $this->sendOrderUpdate($order, 'paid');
    }

    public function sendOrderStatus(array $order, string $status, ?string $reason = null): bool
    {
        return $this->sendOrderUpdate($order, $status, $reason);
    }

    private function sendOrderUpdate(array $order, string $event, ?string $reason = null): bool
    {
        $orderId = (int) ($order['id'] ?? 0);
        $eventCopy = [
            'placed' => ['We have received your order.', 'Order received'],
            'paid' => ['Your payment was received and your order is confirmed.', 'Payment confirmed'],
            'confirmed' => ['Your order has been confirmed.', 'Order confirmed'],
            'processing' => ['We are preparing your fragrances with care.', 'Order being prepared'],
            'shipped' => ['Your order is on its way.', 'Order on its way'],
            'delivered' => ['Your order has been delivered. We hope you love it.', 'Order delivered'],
            'cancelled' => ['Your order has been cancelled.', 'Order cancelled'],
        ];
        if ($orderId < 1 || !isset($eventCopy[$event]) || empty($order['email'])) {
            error_log('Order email skipped: invalid event, order, or recipient.');
            return false;
        }
        [$intro, $label] = $eventCopy[$event];
        $name = htmlspecialchars((string) ($order['customer_name'] ?? 'there'), ENT_QUOTES, 'UTF-8');
        $items = is_array($order['items'] ?? null) ? $order['items'] : [];
        $rows = '';
        $plainItems = [];
        foreach ($items as $item) {
            $product = htmlspecialchars((string) ($item['product_name'] ?? 'Fragrance'), ENT_QUOTES, 'UTF-8');
            $imageUrl = trim((string) ($item['image_path'] ?? ''));
            $hasPublicImage = self::isPublicImageUrl($imageUrl);
            $imageCell = $hasPublicImage
                ? '<td width="76" valign="middle" style="padding:10px 8px;border-bottom:1px solid #eee4df"><img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $product . '" width="64" style="display:block;width:64px;height:76px;object-fit:contain;background:#f8f3ef;border-radius:8px"></td>'
                : '<td width="76" style="padding:10px 8px;border-bottom:1px solid #eee4df"><div style="width:64px;height:76px;background:#f8f3ef;border-radius:8px"></div></td>';
            $size = (int) ($item['size_ml'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
            $line = $unit * $quantity;
            $rows .= '<tr>' . $imageCell . '<td style="padding:12px 8px;border-bottom:1px solid #eee4df">' . $product . '<div style="color:#8b7770;font-size:13px">' . $size . ' ml · Qty ' . $quantity . '</div></td><td align="right" style="padding:12px 8px;border-bottom:1px solid #eee4df;white-space:nowrap">' . self::money($line) . '</td></tr>';
            $plainItems[] = ($item['product_name'] ?? 'Fragrance') . " ({$size} ml) × {$quantity}: " . self::money($line);
        }
        $reasonBlock = '';
        $plainReason = '';
        if ($event === 'cancelled' && trim((string) $reason) !== '') {
            $safeReason = htmlspecialchars(trim((string) $reason), ENT_QUOTES, 'UTF-8');
            $reasonBlock = '<div style="background:#f8eeeb;border-left:3px solid #754b50;padding:14px 16px;margin:20px 0"><strong>Reason</strong><br>' . nl2br($safeReason) . '</div>';
            $plainReason = "\nReason: " . trim((string) $reason) . "\n";
        }
        $address = array_filter([
            $order['street'] ?? '', $order['area'] ?? '', $order['city'] ?? '', $order['building'] ?? ''
        ], static fn($part) => trim((string) $part) !== '');
        $safeAddress = htmlspecialchars(implode(', ', $address), ENT_QUOTES, 'UTF-8');
        $paymentMethod = strtolower((string) ($order['payment_method'] ?? 'cash')) === 'visa' ? 'Card' : 'Cash on delivery';
        $paymentState = (string) ($order['payment_status'] ?? 'pending');
        $status = htmlspecialchars(ucfirst((string) ($order['status'] ?? $event)), ENT_QUOTES, 'UTF-8');
        $customerGreeting = $name === '' ? 'Hello,' : 'Hello ' . $name . ',';
        $subject = "{$label} · Order #{$orderId} | ater";
        $html = '<!doctype html><html><body style="margin:0;background:#f7f2ee;color:#392f2d;font-family:Arial,Helvetica,sans-serif"><div style="max-width:640px;margin:28px auto;background:#fffdfa;border:1px solid #eadfd8"><div style="padding:28px 34px;border-bottom:1px solid #eadfd8;text-align:center"><div style="font-family:Georgia,serif;font-size:36px;letter-spacing:-2px;color:#754b50">ater<sup style="font:12px Arial">®</sup></div><div style="font-size:10px;letter-spacing:3px;color:#9a7770;margin-top:3px">FRAGRANCE HOUSE</div></div><div style="padding:30px 34px"><p style="color:#754b50;font-size:11px;letter-spacing:2px;font-weight:bold">' . strtoupper(htmlspecialchars($label, ENT_QUOTES, 'UTF-8')) . '</p><h1 style="font-family:Georgia,serif;font-weight:normal;font-size:30px;margin:10px 0 14px">' . $customerGreeting . '</h1><p style="line-height:1.7;color:#675b57">' . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</p>' . $reasonBlock . '<div style="background:#f8f3ef;padding:16px 18px;margin:22px 0"><strong>Order #' . $orderId . '</strong><span style="float:right;color:#754b50;font-weight:bold">' . $status . '</span></div><table role="presentation" width="100%" cellspacing="0" style="border-collapse:collapse"><thead><tr><th colspan="2" align="left" style="padding:8px;border-bottom:1px solid #d9c9c2;color:#806e68;font-size:11px;letter-spacing:1px">YOUR FRAGRANCES</th><th align="right" style="padding:8px;border-bottom:1px solid #d9c9c2;color:#806e68;font-size:11px;letter-spacing:1px">TOTAL</th></tr></thead><tbody>' . ($rows ?: '<tr><td colspan="3" style="padding:12px">Order details are available in your account.</td></tr>') . '</tbody></table><table role="presentation" width="100%" cellspacing="0" style="margin-top:14px;color:#675b57;font-size:14px"><tr><td style="padding:5px 8px">Subtotal</td><td align="right">' . self::money($order['subtotal'] ?? 0) . '</td></tr><tr><td style="padding:5px 8px">Discount</td><td align="right">−' . self::money($order['discount_amount'] ?? 0) . '</td></tr><tr><td style="padding:5px 8px">Delivery</td><td align="right">' . ((float) ($order['delivery_fee'] ?? 0) === 0.0 ? 'Complimentary' : self::money($order['delivery_fee'])) . '</td></tr><tr><td style="padding:13px 8px;border-top:1px solid #d9c9c2;font-weight:bold;color:#392f2d">Total</td><td align="right" style="padding:13px 8px;border-top:1px solid #d9c9c2;font-weight:bold;color:#754b50;font-size:18px">' . self::money($order['total_amount'] ?? 0) . '</td></tr></table><div style="padding-top:18px;margin-top:16px;border-top:1px solid #eee4df;font-size:13px;line-height:1.7;color:#756762"><strong>Payment</strong>: ' . htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8') . ' · ' . htmlspecialchars(ucfirst($paymentState), ENT_QUOTES, 'UTF-8') . '<br><strong>Delivery to</strong>: ' . ($safeAddress ?: 'Address on your order') . '</div><p style="margin:26px 0 4px;color:#675b57">Thank you for choosing ater.</p></div><div style="padding:18px 34px;background:#f8f3ef;color:#8b7770;font-size:12px;text-align:center">A considered collection of fragrance · Amman, Jordan</div></div></body></html>';
        $plain = "{$customerGreeting}\n{$intro}\n\nOrder #{$orderId} — {$status}\n\n" . implode("\n", $plainItems) . "\n\nSubtotal: " . self::money($order['subtotal'] ?? 0) . "\nDiscount: " . self::money($order['discount_amount'] ?? 0) . "\nDelivery: " . self::money($order['delivery_fee'] ?? 0) . "\nTotal: " . self::money($order['total_amount'] ?? 0) . "\nPayment: {$paymentMethod} ({$paymentState})\nDelivery to: " . implode(', ', $address) . $plainReason . "\nThank you for choosing ater.";
        return $this->send((string) $order['email'], $subject, $html, $plain);
    }

    private function send(string $email, string $subject, string $content, string $plain): bool
    {
        if (!class_exists(PHPMailer::class)) {
            error_log('Email was not sent: Composer dependencies are unavailable.');
            return false;
        }
        try {
            $mail = new PHPMailer(true);
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $this->config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['username'];
            $mail->Password = $this->config['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = $this->config['port'];
            $mail->setFrom($this->config['username'], 'ater Fragrance House');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->AltBody = $plain;
            return $mail->send();
        } catch (Throwable $e) {
            error_log('Email sending failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function money($value): string
    {
        return number_format((float) $value, 3, '.', ',') . ' JOD';
    }

    private static function isPublicImageUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (!in_array($scheme, ['https', 'http'], true) || $host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.local') || str_ends_with($host, '.test')) {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }
        return true;
    }
}
