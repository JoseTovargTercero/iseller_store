<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$input = json_decode(file_get_contents('php://input'), true);
$user_ids = $input['user_ids'] ?? [];

if (empty($user_ids) || !is_array($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'No se especificaron usuarios']);
    exit;
}

// Sanitize IDs
$user_ids = array_map('intval', $user_ids);
$user_ids = array_filter($user_ids, fn($id) => $id > 0);

if (empty($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'IDs de usuario inválidos']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($user_ids), '?'));
$types        = str_repeat('i', count($user_ids));

$stmt = $conexion_store->prepare("
    SELECT id, nombre, email
    FROM usuarios
    WHERE id IN ($placeholders) AND email IS NOT NULL AND email != ''
");
$stmt->bind_param($types, ...$user_ids);
$stmt->execute();
$result = $stmt->get_result();

$sent   = 0;
$failed = 0;
$errors = [];

while ($user = $result->fetch_assoc()) {
    $ok = enviarCorreoRecompensa($user['email'], $user['nombre']);
    if ($ok) {
        $sent++;
    } else {
        $failed++;
        $errors[] = $user['email'];
    }
}

echo json_encode([
    'success' => true,
    'sent'    => $sent,
    'failed'  => $failed,
    'errors'  => $errors,
]);

/**
 * Envía el correo de recompensa disponible al usuario.
 */
function enviarCorreoRecompensa(string $to, string $nombre): bool
{
    if (!$to) return false;

    $from    = 'contacto@iseller-tiendas.com';
    $subject = 'Tienes una recompensa disponible en tu cuenta - iSeller Tiendas';
    $storeUrl = 'https://iseller-tiendas.com';

    $nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

    $message = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tu recompensa te espera — iSeller Tiendas</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f9;
      color: #333;
      line-height: 1.7;
    }
    .wrapper {
      max-width: 620px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }
    /* Header */
    .header {
      background: linear-gradient(135deg, #5a9e6f 0%, #3d7a52 100%);
      padding: 36px 40px;
      text-align: center;
    }
    .logo-text {
      color: #ffffff;
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }
    .logo-text span {
      color: #a8e6c0;
    }
    .header-tagline {
      color: rgba(255,255,255,0.80);
      font-size: 13px;
      margin-top: 4px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }
    /* Hero Banner */
    .hero-banner {
      background: linear-gradient(135deg, #eafaf0 0%, #d4f5e2 100%);
      border-bottom: 2px solid #c0edcf;
      padding: 28px 40px;
      text-align: center;
    }
    .hero-banner .emoji-big {
      font-size: 48px;
      display: block;
      margin-bottom: 10px;
    }
    .hero-banner h1 {
      font-size: 21px;
      font-weight: 700;
      color: #2e6b42;
      line-height: 1.3;
    }
    /* Content */
    .content {
      padding: 36px 40px;
    }
    .greeting {
      font-size: 16px;
      color: #555;
      margin-bottom: 20px;
    }
    .greeting strong {
      color: #222;
    }
    .highlight-block {
      background: #f0fbf4;
      border-left: 4px solid #5a9e6f;
      border-radius: 0 10px 10px 0;
      padding: 18px 20px;
      margin: 24px 0;
    }
    .highlight-block p {
      margin-bottom: 12px;
      font-size: 15px;
      color: #333;
    }
    .highlight-block p:last-child {
      margin-bottom: 0;
    }
    .highlight-block .point-emoji {
      margin-right: 8px;
    }
    /* CTA Button */
    .cta-wrapper {
      text-align: center;
      margin: 32px 0 20px;
    }
    .cta-btn {
      display: inline-block;
      background: linear-gradient(135deg, #5a9e6f 0%, #3d7a52 100%);
      color: #ffffff !important;
      text-decoration: none;
      padding: 16px 42px;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.3px;
      box-shadow: 0 6px 18px rgba(90,158,111,0.4);
    }
    .helping-text {
      font-size: 14px;
      color: #777;
      margin-top: 28px;
      line-height: 1.6;
    }
    /* Divider */
    .divider {
      border: none;
      border-top: 1px solid #eee;
      margin: 28px 0;
    }
    /* Signature */
    .signature {
      font-size: 15px;
      color: #444;
    }
    .signature strong {
      color: #2e6b42;
    }
    /* PS */
    .ps-block {
      background: #fffbeb;
      border: 1px solid #f5e6a3;
      border-radius: 10px;
      padding: 14px 18px;
      font-size: 13px;
      color: #7a6520;
      margin-top: 24px;
    }
    /* Footer */
    .footer {
      background: #f8f9fa;
      border-top: 1px solid #eee;
      padding: 24px 40px;
      text-align: center;
    }
    .footer p {
      font-size: 12px;
      color: #aaa;
      margin-bottom: 4px;
    }
    .footer a {
      color: #5a9e6f;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <!-- Header / Logo -->
    <div class="header">
      <div class="logo-text">i<span>Seller</span> Tiendas</div>
      <div class="header-tagline">Tu tienda, siempre contigo</div>
    </div>

    <!-- Hero -->
    <div class="hero-banner">
      <span class="emoji-big">🎁</span>
      <h1>Tienes una recompensa disponible en tu cuenta</h1>
    </div>

    <!-- Main Content -->
    <div class="content">

      <p class="greeting">Hola, <strong>{$nombreSeguro}</strong></p>

      <p style="font-size:15px; color:#444; margin-bottom: 18px;">
        Queremos informarte que tienes una recompensa disponible en tu cuenta, lista para ser utilizada en tu próxima compra.
      </p>

      <div class="highlight-block">
        <p>Esta recompensa forma parte de nuestro programa de beneficios y puede aplicarse directamente en el carrito al momento de pagar.</p>
      </div>

      <p style="font-size:15px; color:#444; margin-bottom: 8px;">
        👉 Accede a tu cuenta para usar tu recompensa:
      </p>

      <div class="cta-wrapper">
        <a href="{$storeUrl}" class="cta-btn">Ingresar al e-commerce</a>
      </div>

      <p class="helping-text">
        Aprovecha esta oportunidad para ahorrar en tus próximas compras y seguir acumulando puntos y beneficios.
        Si tienes alguna duda, nuestro equipo estará encantado de ayudarte.
      </p>

      <hr class="divider">

      <div class="signature">
        Saludos,<br>
        <strong>Equipo de Iseller Tiendas</strong>
      </div>

    </div>

    <!-- Footer -->
    <div class="footer">
      <p>© 2026 iSeller Tiendas. Todos los derechos reservados.</p>
      <p><a href="{$storeUrl}">{$storeUrl}</a></p>
      <p style="margin-top:8px; font-size:11px; color:#ccc;">
        Recibes este correo porque tienes una cuenta activa en iSeller Tiendas.<br>
        Remitente: contacto@iseller-tiendas.com
      </p>
    </div>

  </div>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: iSeller Tiendas <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($to, $subject, $message, $headers);
}
