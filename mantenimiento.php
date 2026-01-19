<?php
require_once 'core/db.php';

// Fetch configuration again to ensure we have the date for this page context
$query = "SELECT configuracion_hasta FROM configuracion WHERE configuracion = 'mantenimiento' LIMIT 1";
$result = $conexion_store->query($query);
$config_hasta = null;
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $config_hasta = $row['configuracion_hasta'];
}

// Redirect back if maintenance is off (optional check, though core/check_maintenance.php handles this usually if included, but this is a standalone entry point too maybe?)
// Since check_maintenance.php is NOT included here (to avoid loop if it redirects to self, though my logic handled that), we should manually check if we want to kick people out if maintenance is OFF.
// Let's add a quick check.
$check_query = "SELECT mantenimiento FROM configuracion WHERE configuracion = 'mantenimiento' LIMIT 1";
$check_res = $conexion_store->query($check_query);
if ($check_res && $check_res->num_rows > 0) {
    if ($check_res->fetch_assoc()['mantenimiento'] == 0) {
        header("Location: index.php");
        exit();
    }
}

// If no date set, default to something or handle error
if (!$config_hasta) {
     // Fallback or error
     $config_hasta = date('Y-m-d H:i:s', strtotime('+1 hour'));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>En Mantenimiento</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --primary:#6faf7a;
    --dark:#1f2933;
    --muted:#6b7280;
    --bg1:#f8fafc;
    --bg2:#eef2f7;
}

*{box-sizing:border-box}

body{
    margin:0;
    font-family:'Inter',sans-serif;
    min-height:100vh;
    background:linear-gradient(135deg,var(--bg1),var(--bg2));
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.maintenance-card{
    background:#fff;
    max-width:900px;
    width:100%;
    border-radius:20px;
    box-shadow:0 20px 50px rgba(0,0,0,.08);
    display:grid;
    grid-template-columns:1fr 1fr;
    overflow:hidden;
}

@media(max-width:768px){
    .maintenance-card{
        grid-template-columns:1fr;
    }
}

.left{
    padding:3rem;
}

.right{
    background:linear-gradient(160deg,#e9f7ee,#ffffff);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem;
}

h1{
    color:var(--dark);
    margin:0 0 1rem;
    font-size:2rem;
}

p{
    color:var(--muted);
    font-size:1.05rem;
    margin-bottom:2rem;
}

.badge{
    display:inline-block;
    background:rgba(111,175,122,.15);
    color:var(--primary);
    padding:.4rem .9rem;
    border-radius:999px;
    font-weight:600;
    font-size:.9rem;
    margin-bottom:1rem;
}

.timer{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

.time-box{
    background:#f8fafc;
    border-radius:14px;
    padding:14px 18px;
    min-width:80px;
    text-align:center;
    box-shadow:inset 0 0 0 1px #e5e7eb;
}

.time-box span{
    display:block;
    font-size:1.6rem;
    font-weight:700;
    color:var(--primary);
}

.time-box small{
    color:#6b7280;
    font-size:.75rem;
    text-transform:uppercase;
    letter-spacing:.05em;
}

.footer-msg{
    margin-top:1.5rem;
    color:#9ca3af;
    font-size:.9rem;
}

/* Simple illustration */
svg{
    max-width:100%;
    height:auto;
}
</style>
</head>

<body>

<div class="maintenance-card">

    <div class="left">
        <div class="badge">Modo mantenimiento</div>
        <h1>Estamos mejorando para ti 🚀</h1>
        <p>
            Nuestro sistema se encuentra en mantenimiento programado.
            Muy pronto volveremos con mejoras para ofrecerte un mejor servicio.
            <b>
        Si tienes una compra confirmada o en proceso, no te preocupes:
        tu pedido será enviado con normalidad o te notificaremos cuando esté listo para retirar.
    </b>
        </p>

<div class="mb-3">
        <strong>Volveremos en:</strong>
        <br>
        <br>
</div>
        <div id="countdown" class="timer"></div>

        <div class="footer-msg">
            Gracias por tu paciencia 💚
        </div>
    </div>

    <div class="right">
        <!-- Ilustración SVG -->
        <svg viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
            <circle cx="200" cy="150" r="120" fill="#e6f4ea"/>
            <rect x="120" y="110" width="160" height="100" rx="12" fill="#6faf7a"/>
            <rect x="140" y="130" width="120" height="15" rx="6" fill="#ffffff"/>
            <rect x="140" y="160" width="90" height="15" rx="6" fill="#ffffff"/>
            <circle cx="200" cy="95" r="22" fill="#6faf7a"/>
            <rect x="190" y="50" width="20" height="40" rx="8" fill="#6faf7a"/>
        </svg>
    </div>

</div>

<script>
const targetDate = new Date("<?php echo $config_hasta; ?>").getTime();
const countdownEl = document.getElementById("countdown");

const x = setInterval(() => {

    const now = new Date().getTime();
    const distance = targetDate - now;

    if (distance <= 0) {
        clearInterval(x);
        countdownEl.innerHTML = "<strong>¡Ya estamos de vuelta!</strong>";
        setTimeout(() => location.reload(), 3000);
        return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    let html = "";

    if (days > 0) {
        html += box(days, "Días");
    }

    if (hours > 0 || days > 0) {
        html += box(hours, "Horas");
    }

    html += box(minutes, "Min");
    html += box(seconds, "Seg");

    countdownEl.innerHTML = html;

}, 1000);

function box(value, label){
    return `
        <div class="time-box">
            <span>${value}</span>
            <small>${label}</small>
        </div>
    `;
}
</script>

</body>
</html>
