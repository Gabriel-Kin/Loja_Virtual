<?php

class EnderecoDAO {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /** Insere um Endereco e retorna o ID gerado. */
    public function inserir(Endereco $endereco) {
        $sql = "INSERT INTO ENDERECO (CIDADE, ESTADO, RUA, NUMERO, COMPLEMENTO, BAIRRO, CEP)
                VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING ENDERECO_ID";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $endereco->cidade, $endereco->estado, $endereco->rua, $endereco->numero,
            $endereco->complemento ?? '', $endereco->bairro, $endereco->cep
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['endereco_id'] ?? $row['ENDERECO_ID'];
    }

    public function atualizar(Endereco $endereco) {
        $sql = "UPDATE ENDERECO
                SET RUA = ?, NUMERO = ?, BAIRRO = ?, CIDADE = ?, ESTADO = ?, CEP = ?
                WHERE ENDERECO_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $endereco->rua, $endereco->numero, $endereco->bairro,
            $endereco->cidade, $endereco->estado, $endereco->cep, $endereco->endereco_id
        ]);
    }

    public function excluir($endereco_id) {
        $stmt = $this->conn->prepare("DELETE FROM ENDERECO WHERE ENDERECO_ID = ?");
        return $stmt->execute([$endereco_id]);
    }

    public function buscarPorId($endereco_id) {
        $stmt = $this->conn->prepare("SELECT * FROM ENDERECO WHERE ENDERECO_ID = ?");
        $stmt->execute([$endereco_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
