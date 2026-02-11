<?php
require_once __DIR__ . '/../core/db.php';

class SystemUserModel
{
    private $db;
    private $table = 'usuarios';

    public function __construct()
    {
        global $conexion_store;
        $this->db = $conexion_store;
    }

    /**
     * Login simple por email.
     */
    public function obtenerPorEmail(string $email): ?array
    {
        $sql = "SELECT id, nombre, email, password, estado 
                FROM {$this->table} 
                WHERE email = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new mysqli_sql_exception("Error preparando consulta: " . $this->db->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if (!$res || $res->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $user = $res->fetch_assoc();
        $stmt->close();
        return $user;
    }

    /**
     * Verifica la contraseña del usuario.
     */
    public function verificarPassword(array $usuario, string $password): bool
    {
        if (empty($usuario['password'])) {
            return false;
        }

        // Usuario desactivado
        if (isset($usuario['estado']) && (int)$usuario['estado'] === 0) {
            return false;
        }

        return password_verify($password, $usuario['password']);
    }

    /**
     * Realiza el login básico para la app.
     */
    public function loginApp(string $email, string $password): array
    {
        $user = $this->obtenerPorEmail($email);

        if (!$user) {
            return [
                'verificado' => false,
                'mensaje' => 'Usuario no encontrado.'
            ];
        }

        if ((int)$user['estado'] === 0) {
            return [
                'verificado' => false,
                'mensaje' => 'El usuario está desactivado.'
            ];
        }

        if (!$this->verificarPassword($user, $password)) {
            return [
                'verificado' => false,
                'mensaje' => 'Contraseña incorrecta.'
            ];
        }

        return [
            'verificado' => true,
            'mensaje' => 'Login exitoso.',
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email']
            ]
        ];
    }
}
