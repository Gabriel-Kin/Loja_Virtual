<?php
class Database {
    private static $envLoaded = false;

    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    public $conn; 

    public function __construct() {
        $this->loadEnv();

        $this->host = $this->env("DB_HOST");
        $this->port = $this->env("DB_PORT");
        $this->db_name = $this->env("DB_DATABASE");
        $this->username = $this->env("DB_USERNAME");
        $this->password = $this->env("DB_PASSWORD");
    }

    private function loadEnv() {
        if (self::$envLoaded) {
            return;
        }

        $envPath = __DIR__ . "/../.env";

        if (file_exists($envPath)) {
            $linhas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($linhas as $linha) {
                $linha = trim($linha);

                if ($linha === "" || strpos($linha, "#") === 0 || strpos($linha, "=") === false) {
                    continue;
                }

                [$chave, $valor] = explode("=", $linha, 2);
                $chave = trim($chave);
                $valor = trim($valor, " \t\n\r\0\x0B\"'");

                if (getenv($chave) === false) {
                    putenv($chave . "=" . $valor);
                    $_ENV[$chave] = $valor;
                }
            }
        }

        self::$envLoaded = true;
    }

    private function env($chave, $padrao = null) {
        $valor = getenv($chave);
        return $valor !== false ? $valor : $padrao;
    }

    public function getConnection() {
        $this->conn = null;
        try {
            // Driver pgsql para PostgreSQL
            $this->conn = new PDO("pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Erro na conexão: " . $e->getMessage();
        }
        return $this->conn;
    }
}
