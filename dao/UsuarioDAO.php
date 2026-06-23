<?php

class UsuarioDAO {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /** Insere um Usuario e retorna o ID gerado. */
    public function inserir(Usuario $usuario) {
        $sql = "INSERT INTO USUARIO (EMAIL, SENHA, TIPO)
                VALUES (:email, :senha, :tipo) RETURNING USUARIO_ID";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':email' => $usuario->email,
            ':senha' => $usuario->senha,
            ':tipo'  => $usuario->tipo
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['usuario_id'] ?? $row['USUARIO_ID'];
    }

    /** Atualiza email, senha e tipo de um usuário. */
    public function atualizar(Usuario $usuario) {
        $sql = "UPDATE USUARIO SET EMAIL = ?, SENHA = ?, TIPO = ? WHERE USUARIO_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $usuario->email, $usuario->senha, $usuario->tipo, $usuario->usuario_id
        ]);
    }

    /** Atualiza apenas email e senha (mantém o tipo atual). */
    public function atualizarCredenciais($usuario_id, $email, $senha) {
        $sql = "UPDATE USUARIO SET EMAIL = ?, SENHA = ? WHERE USUARIO_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$email, $senha, $usuario_id]);
    }

    public function excluir($usuario_id) {
        $stmt = $this->conn->prepare("DELETE FROM USUARIO WHERE USUARIO_ID = ?");
        return $stmt->execute([$usuario_id]);
    }

    /**
     * Exclui o usuário e os registros dependentes do seu perfil
     * (administrador / cliente / fornecedor) e os endereços associados.
     * Deve ser chamado dentro de uma transação.
     * Lança exceção (FK) se o usuário for fornecedor com produtos vinculados.
     */
    public function excluirComDependencias($usuario_id) {
        // Administrador (não possui endereço próprio)
        $this->conn->prepare("DELETE FROM ADMINISTRADOR WHERE USUARIO_ID = ?")
                   ->execute([$usuario_id]);

        // Cliente -> remove cliente e seu endereço
        $stmt = $this->conn->prepare("SELECT ENDERECO_ID FROM CLIENTE WHERE USUARIO_ID = ?");
        $stmt->execute([$usuario_id]);
        if ($cli = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->prepare("DELETE FROM CLIENTE WHERE USUARIO_ID = ?")->execute([$usuario_id]);
            $this->conn->prepare("DELETE FROM ENDERECO WHERE ENDERECO_ID = ?")
                       ->execute([$cli['endereco_id'] ?? $cli['ENDERECO_ID']]);
        }

        // Fornecedor -> remove fornecedor e seu endereço (falha se houver produtos)
        $stmt = $this->conn->prepare("SELECT ENDERECO_ID FROM FORNECEDOR WHERE USUARIO_ID = ?");
        $stmt->execute([$usuario_id]);
        if ($forn = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->conn->prepare("DELETE FROM FORNECEDOR WHERE USUARIO_ID = ?")->execute([$usuario_id]);
            $this->conn->prepare("DELETE FROM ENDERECO WHERE ENDERECO_ID = ?")
                       ->execute([$forn['endereco_id'] ?? $forn['ENDERECO_ID']]);
        }

        // Por fim, o usuário
        return $this->excluir($usuario_id);
    }

    public function buscarPorId($usuario_id) {
        $stmt = $this->conn->prepare("SELECT * FROM USUARIO WHERE USUARIO_ID = ?");
        $stmt->execute([$usuario_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscarPorEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM USUARIO WHERE EMAIL = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** Lista usuários, opcionalmente filtrando por email (ILIKE) ou ID. */
    public function listar($busca = "") {
        if ($busca !== "") {
            $sql = "SELECT * FROM USUARIO
                    WHERE EMAIL ILIKE ? OR CAST(USUARIO_ID AS TEXT) = ?
                    ORDER BY USUARIO_ID";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(["%$busca%", $busca]);
        } else {
            $stmt = $this->conn->query("SELECT * FROM USUARIO ORDER BY USUARIO_ID");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
