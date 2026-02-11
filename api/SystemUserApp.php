<?php
require_once 'SystemUserModel.php';
require_once __DIR__ . '/../core/session.php';

class SystemUserController
{
    private $model;

    public function __construct()
    {
        $this->model = new SystemUserModel();
    }

    private function jsonResponse($value, string $message = '', $data = null, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'value' => $value,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }

    public function loginApp(): void
    {
        // 1) Entradas
        $email = trim((string) ($_POST['email'] ?? ''));
        //$email = 'ac.80014.dc@gmail.com';
        $password = (string) ($_POST['password'] ?? ($_POST['contrasena'] ?? ''));
        //$password = '123456';



        
        // 2) Validaciones
        if ($email === '' || $password === '') {
            $this->jsonResponse(false, 'Email y contraseña son obligatorios.', null, 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(false, 'El formato del correo electrónico no es válido.', null, 400);
            return;
        }

        try {
            // ----- AUTENTICACIÓN -----
            $auth = $this->model->loginApp($email, $password);

            if (!$auth['verificado']) {
                $status = 401;
                if (stripos($auth['mensaje'], 'desactivado') !== false) {
                    $status = 403;
                }
                $this->jsonResponse(false, $auth['mensaje'], null, $status);
                return;
            }

            // ----- ÉXITO -----
            $usuario = $auth['user'];

            // Iniciar sesión estándar del proyecto
            loginUser($usuario);

            // ----- Payload -----
            $data = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'redirect_url' => 'index.php'
            ];

            $this->jsonResponse(true, 'Inicio de sesión exitoso.', $data, 200);
        } catch (Throwable $e) {
            error_log("Error en loginApp: " . $e->getMessage());
            $this->jsonResponse(false, 'Error interno del servidor.', null, 500);
        }
    }

    /**
     * Verifica si hay una sesión activa.
     */
    public function verificarLoginApp(): void
    {
        if (isLoggedIn()) {
            $this->jsonResponse(true, 'Sesión activa.', [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_nombre'],
                'email' => $_SESSION['user_email']
            ], 200);
        } else {
            $this->jsonResponse(false, 'No hay sesión activa.', null, 401);
        }
    }
}

$accion = $_GET['accion'] ?? '';
$controller = new SystemUserController();

switch ($accion) {
    case 'loginApp':
        $controller->loginApp();
        break;
    case 'verificarLoginApp':
        $controller->verificarLoginApp();
        break;
    default:
        $controller->jsonResponse(false, 'Acción no reconocida.', null, 400);
        break;
}
