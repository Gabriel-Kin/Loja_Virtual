DO $$
DECLARE
    -- Senha padrão criptografada com bcrypt: "senha123"
    V_SENHA TEXT := '$2b$10$0Yx/xGqjAy6MBM8XRn.yUucPQTZoF9ghffZz9yEY/vbvthiLb4nSm';
    V_USUARIO_ID INTEGER;
    V_ENDERECO_ID INTEGER;
    I INTEGER;
    
    -- Arrays para variação de dados de teste
    NOMES TEXT[] := ARRAY['Carlos Silva', 'Mariana Souza', 'Lucas Pereira', 'Juliana Costa', 'Rodrigo Alves', 'Fernanda Lima', 'Gabriel Santos', 'Beatriz Oliveira', 'Ricardo Ferreira', 'Camila Rodrigues'];
    CIDADES TEXT[] := ARRAY['Caxias do Sul', 'Farroupilha', 'Bento Gonçalves', 'Flores da Cunha', 'Garibaldi'];
    BAIRROS TEXT[] := ARRAY['Centro', 'São Luiz', 'Cinco de Maio', 'Cruzeiro', 'Pioneiro'];
    RUAS TEXT[] := ARRAY['Av. Júlio de Castilhos', 'Rua Pinheiro Machado', 'Rua Independência', 'Av. Rio Branco', 'Rua Marechal Deodoro'];
BEGIN
    FOR I IN 1..30 LOOP
        -- 1. Cria o Usuário (Tipo 2 = Cliente)
        INSERT INTO USUARIO (EMAIL, SENHA, TIPO) 
        VALUES (
            'cliente' || I || '@techstore.com', 
            V_SENHA, 
            2
        )
        ON CONFLICT (EMAIL) DO UPDATE SET EMAIL = EXCLUDED.EMAIL
        RETURNING USUARIO_ID INTO V_USUARIO_ID;

        -- 2. Cria o Endereço com dados rotativos baseados nos arrays
        INSERT INTO ENDERECO (CIDADE, ESTADO, RUA, NUMERO, COMPLEMENTO, BAIRRO, CEP)
        VALUES (
            CIDADES[mod(I, 5) + 1],
            'RS',
            RUAS[mod(I, 5) + 1],
            CAST(100 + I AS TEXT),
            CASE WHEN I % 2 = 0 THEN 'Ap ' || (I * 2) ELSE NULL END,
            BAIRROS[mod(I, 5) + 1],
            '95000-' || LPAD(CAST(I AS TEXT), 3, '0')
        )
        RETURNING ENDERECO_ID INTO V_ENDERECO_ID;

        -- 3. Cria o Perfil do Cliente vinculando o Usuário e Endereço gerados
        INSERT INTO CLIENTE (USUARIO_ID, ENDERECO_ID, NOME, TELEFONE, CARTAO_CREDITO)
        VALUES (
            V_USUARIO_ID,
            V_ENDERECO_ID,
            NOMES[mod(I, 10) + 1] || ' ' || I, -- Garante nomes diferentes adicionando o número
            '54999' || LPAD(CAST(1000 + I AS TEXT), 4, '0'),
            '444455556666' || LPAD(CAST(I AS TEXT), 4, '0')
        );
    END LOOP;
END $$;