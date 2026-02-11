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
                'session_id' => $usuario['session_id'] ?? null,
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
        $token = $_POST['token'] ?? null;

        if (!$token) {
            $this->jsonResponse(false, 'No se recibió el token de la sesión.', null, 401);
            return;
        }

        $user = $this->model->obtenerUsuarioPorToken($token);

        if ($user === null) {
            $this->jsonResponse(false, 'Token de sesión inválido o expirado.', null, 401);
            return;
        }

        try {
            $auth = $this->model->loginPassLeft($user['email']);

            if (!$auth['verificado']) {
                $this->jsonResponse(false, 'Error al recuperar datos del usuario.', null, 500);
                return;
            }

            $usuario = $auth['user'];

            // Iniciar sesión estándar del proyecto utilizando core/session.php
            loginUser($usuario);

            // También guardamos el session_id en la sesión para futura referencia
            $_SESSION['session_id'] = $user['session_id'];

            // Descartar el token después de usarlo (según lógica propuesta del usuario)
            $this->model->descartarToken($token);

            $data = [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'session_id' => $user['session_id'],
                'redirect_url' => 'index.php'
            ];

            $this->jsonResponse(true, 'Sesión restaurada con éxito.', $data, 200);
        } catch (Throwable $e) {
            error_log("Error en verificarLoginApp: " . $e->getMessage());
            $this->jsonResponse(false, 'Error interno del servidor: ' . $e->getMessage(), null, 500);
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
