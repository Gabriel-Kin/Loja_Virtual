DO $$
DECLARE
    -- Senha padrão criptografada com bcrypt: "senha123"
    V_SENHA TEXT := '$2b$10$0Yx/xGqjAy6MBM8XRn.yUucPQTZoF9ghffZz9yEY/vbvthiLb4nSm';
    V_USUARIO_ID INTEGER;
    V_ENDERECO_ID INTEGER;
    I INTEGER;
    
    -- Arrays para gerar dados de teste dinâmicos e variados
    NOMES_EMPRESAS TEXT[] := ARRAY['TechDistribuidores', 'Global Componentes', 'Logística Sul', 'Mega Atacado', 'Infinity Eletrônicos', 'Nexus Tech', 'Central Suprimentos', 'Alpha Equipamentos', 'Prime Business', 'Brasil Connect'];
    CIDADES TEXT[] := ARRAY['Porto Alegre', 'Caxias do Sul', 'Novo Hamburgo', 'Canoas', 'Passo Fundo'];
    BAIRROS TEXT[] := ARRAY['Distrito Industrial', 'Anchieta', 'Centro', 'Cinco de Maio', 'Floresta'];
    RUAS TEXT[] := ARRAY['Av. das Indústrias', 'Rua Voluntários da Pátria', 'Av. Guilherme Schell', 'Rua Getúlio Vargas', 'Av. Sertório'];
    DESCRICOES TEXT[] := ARRAY['Distribuição de componentes de hardware em grande escala.', 'Importação e comércio atacadista de periféricos de informática.', 'Fornecimento ágil de cabos, conectores e redes.', 'Suprimentos corporativos de escritório e eletrônicos de consumo.'];
BEGIN
    FOR I IN 1..30 LOOP
        -- 1. Cria o Usuário (Tipo 3 = Fornecedor)
        INSERT INTO USUARIO (EMAIL, SENHA, TIPO) 
        VALUES (
            'fornecedor' || I || '@loja.com', 
            V_SENHA, 
            3
        )
        ON CONFLICT (EMAIL) DO UPDATE SET EMAIL = EXCLUDED.EMAIL
        RETURNING USUARIO_ID INTO V_USUARIO_ID;

        -- 2. Cria o Endereço com dados rotativos baseados nos arrays
        INSERT INTO ENDERECO (CIDADE, ESTADO, RUA, NUMERO, COMPLEMENTO, BAIRRO, CEP)
        VALUES (
            CIDADES[mod(I, 5) + 1],
            'RS',
            RUAS[mod(I, 5) + 1],
            CAST(500 + I AS TEXT),
            CASE WHEN I % 3 = 0 THEN 'Galpão ' || (I / 3) ELSE 'Sala ' || I END,
            BAIRROS[mod(I, 5) + 1],
            '90000-' || LPAD(CAST(I AS TEXT), 3, '0')
        )
        RETURNING ENDERECO_ID INTO V_ENDERECO_ID;

        -- 3. Cria o Perfil do Fornecedor amarrando os vínculos
        INSERT INTO FORNECEDOR (USUARIO_ID, ENDERECO_ID, NOME, DESCRICAO, TELEFONE)
        VALUES (
            V_USUARIO_ID,
            V_ENDERECO_ID,
            NOMES_EMPRESAS[mod(I, 10) + 1] || ' S.A. ' || I, -- Nome único com sufixo numérico
            DESCRICOES[mod(I, 4) + 1],
            '513333' || LPAD(CAST(I AS TEXT), 4, '0')
        );
    END LOOP;
END $$;