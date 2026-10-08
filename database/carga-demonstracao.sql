-- Carga de demonstração do transporte escolar.
-- Repetir o script substitui apenas as linhas marcadas com origem_chave ou texto "Carga".
-- Mantém os protocolos TE-2026-000001, TE-2026-000002, OS-2026-000001 e AB-2026-000001.

BEGIN;

CREATE OR REPLACE FUNCTION pg_temp.digito(corpo text) RETURNS int
LANGUAGE plpgsql AS $$
DECLARE
    soma int := 0;
    i int;
BEGIN
    FOR i IN 1..length(corpo) LOOP
        soma := soma + i * ascii(substr(corpo, i, 1));
    END LOOP;
    RETURN soma % 10;
END $$;

CREATE OR REPLACE FUNCTION pg_temp.proto(prefixo text, ano int, seq int) RETURNS text
LANGUAGE plpgsql AS $$
DECLARE
    corpo text := prefixo || '-' || ano || '-' || lpad(seq::text, 6, '0');
BEGIN
    RETURN corpo || '-' || pg_temp.digito(corpo);
END $$;

-- Pessoas da carga (a partir de 700, acima do cadastro local).
INSERT INTO cadastro.pessoa (idpes, nome, data_cad, tipo, situacao, origem_gravacao, operacao)
SELECT id, nome, now(), tipo, 'A', 'U', 'I'
FROM (VALUES
    (700, 'Cooperativa Aurópolis Transporte', 'J'),
    (701, 'Oficina do Vale', 'J'),
    (702, 'Posto Central Aurópolis', 'J'),
    (703, 'Carlos Mendes', 'F'),
    (704, 'Ana Ribeiro', 'F'),
    (705, 'Lúcia Ferreira', 'F'),
    (706, 'Pedro Alves', 'F')
) AS dados(id, nome, tipo)
WHERE NOT EXISTS (SELECT 1 FROM cadastro.pessoa p WHERE p.idpes = dados.id);

INSERT INTO cadastro.juridica (idpes, cnpj, fantasia, origem_gravacao, data_cad, operacao)
SELECT id, cnpj, fantasia, 'U', now(), 'I'
FROM (VALUES
    (700, '90000000000191', 'Cooperativa Aurópolis'),
    (701, '90000000000272', 'Oficina do Vale'),
    (702, '90000000000353', 'Posto Central')
) AS dados(id, cnpj, fantasia)
WHERE NOT EXISTS (SELECT 1 FROM cadastro.juridica j WHERE j.idpes = dados.id);

INSERT INTO cadastro.fisica (idpes, data_nasc, sexo, origem_gravacao, data_cad, operacao)
SELECT id, nascimento, sexo, 'U', now(), 'I'
FROM (VALUES
    (703, DATE '1982-03-14', 'M'),
    (704, DATE '1990-11-02', 'F'),
    (705, DATE '1988-07-19', 'F'),
    (706, DATE '1995-01-30', 'M')
) AS dados(id, nascimento, sexo)
WHERE NOT EXISTS (SELECT 1 FROM cadastro.fisica f WHERE f.idpes = dados.id);

INSERT INTO transporte_escolar.empresa (ref_idpes, observacao, origem, origem_chave, created_at, updated_at)
SELECT 700, 'Transporte terceirizado da rede', 'ieducar', 'carga-empresa', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM transporte_escolar.empresa WHERE origem_chave = 'carga-empresa');

INSERT INTO transporte_escolar.fornecedor (ref_idpes, observacao, origem, origem_chave, created_at, updated_at)
SELECT idpes, obs, 'ieducar', chave, now(), now()
FROM (VALUES
    (701::numeric, 'Oficina de manutenção', 'carga-oficina'),
    (702::numeric, 'Posto de abastecimento', 'carga-posto')
) AS dados(idpes, obs, chave)
WHERE NOT EXISTS (SELECT 1 FROM transporte_escolar.fornecedor f WHERE f.origem_chave = dados.chave);

INSERT INTO transporte_escolar.motorista (ref_idpes, empresa_id, cnh, tipo_cnh, dt_habilitacao, vencimento_cnh, vinculo, turno, salario_mensal, carga_horaria, encargo_percent, origem, origem_chave, created_at, updated_at)
SELECT dados.ref_idpes, e.id, dados.cnh, dados.categoria, dados.habilitacao, dados.vencimento, dados.vinculo, 'manha', dados.salario, 40, 20, 'ieducar', dados.chave, now(), now()
FROM transporte_escolar.empresa e
JOIN (VALUES
    (703::numeric, '12345678900', 'D', DATE '2010-05-01', DATE '2026-10-25', 'efetivo', 4200.00, 'carga-motorista-cnh'),
    (704::numeric, '98765432100', 'D', DATE '2014-08-20', DATE '2027-08-20', 'contratado', 3800.00, 'carga-motorista-ok')
) AS dados(ref_idpes, cnh, categoria, habilitacao, vencimento, vinculo, salario, chave) ON true
WHERE e.origem_chave = 'carga-empresa'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.motorista m WHERE m.origem_chave = dados.chave);

INSERT INTO transporte_escolar.monitor (ref_idpes, turno, salario_mensal, carga_horaria, encargo_percent, origem, origem_chave, created_at, updated_at)
SELECT ref_idpes, 'manha', salario, 40, 20, 'ieducar', chave, now(), now()
FROM (VALUES
    (705::numeric, 2200.00, 'carga-monitor-lucia'),
    (706::numeric, 2200.00, 'carga-monitor-pedro')
) AS dados(ref_idpes, salario, chave)
WHERE NOT EXISTS (SELECT 1 FROM transporte_escolar.monitor m WHERE m.origem_chave = dados.chave);

UPDATE transporte_escolar.veiculo
SET descricao = 'Ônibus escolar 01',
    tipo_censo = 3,
    passageiros = 40,
    marca = 'Mercedes',
    ano_fabricacao = 2018,
    ano_modelo = 2019,
    malha = 'P',
    exclusivo_escolar = true,
    consumo_medio_km_l = 3.20,
    qtd_pneus = 6,
    licenciamento_vencimento = DATE '2026-09-15',
    seguro_vencimento = DATE '2026-11-01',
    ipva_vencimento = DATE '2027-03-31',
    vistoria_vencimento = DATE '2027-01-15',
    empresa_id = (SELECT id FROM transporte_escolar.empresa WHERE origem_chave = 'carga-empresa'),
    motorista_id = (SELECT id FROM transporte_escolar.motorista WHERE origem_chave = 'carga-motorista-cnh'),
    origem_chave = 'carga-onibus',
    updated_at = now()
WHERE id = 1;

INSERT INTO transporte_escolar.veiculo (descricao, placa, renavam, marca, ano_fabricacao, ano_modelo, passageiros, malha, tipo_censo, exclusivo_escolar, ativo, consumo_medio_km_l, qtd_pneus, licenciamento_vencimento, empresa_id, motorista_id, origem, origem_chave, created_at, updated_at)
SELECT 'Van da linha rural', 'AUR0B02', '00998877665', 'Renault', 2016, 2017, 8, 'N', 1, true, true, 8.00, 4, DATE '2027-04-01',
       e.id, m.id, 'ieducar', 'carga-van', now(), now()
FROM transporte_escolar.empresa e
JOIN transporte_escolar.motorista m ON m.origem_chave = 'carga-motorista-ok'
WHERE e.origem_chave = 'carga-empresa'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.veiculo WHERE origem_chave = 'carga-van');

INSERT INTO transporte_escolar.veiculo (descricao, placa, renavam, marca, ano_fabricacao, ano_modelo, passageiros, malha, tipo_censo, exclusivo_escolar, adaptado_pcd, ativo, consumo_medio_km_l, empresa_id, origem, origem_chave, created_at, updated_at)
SELECT 'Micro-ônibus infantil', 'AUR0C03', '00998877666', 'Volare', 2020, 2021, 20, 'P', 2, true, true, true, 5.50,
       e.id, 'ieducar', 'carga-micro', now(), now()
FROM transporte_escolar.empresa e
WHERE e.origem_chave = 'carga-empresa'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.veiculo WHERE origem_chave = 'carga-micro');

INSERT INTO transporte_escolar.ponto (descricao, latitude, longitude, raio_metros, origem, origem_chave, created_at, updated_at)
SELECT descricao, lat, lng, 150, 'ieducar', chave, now(), now()
FROM (VALUES
    ('Praça Central', -29.1600000, -51.1900000, 'carga-praca'),
    ('Rua das Flores', -29.1650000, -51.1850000, 'carga-flores'),
    ('Bairro São José', -29.1700000, -51.1800000, 'carga-saojose'),
    ('Escola de Ensino Fundamental', -29.1680000, -51.1790000, 'carga-escola-ef'),
    ('Vila Nova', -29.1750000, -51.1700000, 'carga-vilanova'),
    ('Linha Rural', -29.1900000, -51.2100000, 'carga-rural'),
    ('Escola de Educação Infantil', -29.1720000, -51.1750000, 'carga-escola-ei')
) AS dados(descricao, lat, lng, chave)
WHERE NOT EXISTS (SELECT 1 FROM transporte_escolar.ponto p WHERE p.origem_chave = dados.chave);

UPDATE pmieducar.escola SET latitude = '-29.168000', longitude = '-51.179000' WHERE cod_escola = 2;
UPDATE pmieducar.escola SET latitude = '-29.172000', longitude = '-51.175000' WHERE cod_escola = 3;

INSERT INTO transporte_escolar.rota (ano, descricao, tipo_rota, periodo, sentido, km_pav, km_npav, velocidade_media_kmh, terceirizada, empresa_id, origem, origem_chave, created_at, updated_at)
SELECT 2026, nome, tipo, periodo, 'C', km_pav, km_npav, 40, true, e.id, 'ieducar', chave, now(), now()
FROM transporte_escolar.empresa e
JOIN (VALUES
    ('Linha Rural', 'R', 'manha', 2.00, 12.00, 'carga-rota-rural'),
    ('Linha Infantil', 'U', 'tarde', 6.00, 0.00, 'carga-rota-infantil')
) AS dados(nome, tipo, periodo, km_pav, km_npav, chave) ON true
WHERE e.origem_chave = 'carga-empresa'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.rota r WHERE r.origem_chave = dados.chave);

UPDATE transporte_escolar.rota
SET km_pav = 8, km_npav = 0, empresa_id = (SELECT id FROM transporte_escolar.empresa WHERE origem_chave = 'carga-empresa'), terceirizada = false, origem_chave = 'carga-rota-centro', updated_at = now()
WHERE id = 1;

INSERT INTO transporte_escolar.rota_escola (rota_id, ref_cod_escola)
SELECT r.id, escola
FROM (VALUES
    ('carga-rota-centro', 2),
    ('carga-rota-rural', 2),
    ('carga-rota-infantil', 3)
) AS dados(chave, escola)
JOIN transporte_escolar.rota r ON r.origem_chave = dados.chave
ON CONFLICT DO NOTHING;

DELETE FROM transporte_escolar.aviso_familia WHERE token LIKE 'carga-%';
DELETE FROM transporte_escolar.embarque WHERE fila_uuid LIKE 'carga-%';
DELETE FROM transporte_escolar.ocorrencia WHERE fila_uuid LIKE 'carga-%';
DELETE FROM transporte_escolar.viagem WHERE rota_id IN (SELECT id FROM transporte_escolar.rota WHERE origem_chave LIKE 'carga-rota-%');
DELETE FROM transporte_escolar.aluno_rota WHERE rota_id IN (SELECT id FROM transporte_escolar.rota WHERE origem_chave LIKE 'carga-rota-%');
DELETE FROM transporte_escolar.itinerario WHERE rota_id IN (SELECT id FROM transporte_escolar.rota WHERE origem_chave LIKE 'carga-rota-%');

INSERT INTO transporte_escolar.itinerario (rota_id, seq, ponto_id, veiculo_id, hora, tipo)
SELECT r.id, dados.seq, p.id, v.id, dados.hora, dados.tipo
FROM (VALUES
    ('carga-rota-centro', 1, 'carga-praca', 'carga-onibus', TIME '06:40', 'I'),
    ('carga-rota-centro', 2, 'carga-flores', 'carga-onibus', TIME '06:50', 'I'),
    ('carga-rota-centro', 3, 'carga-saojose', 'carga-onibus', TIME '07:00', 'I'),
    ('carga-rota-centro', 4, 'carga-escola-ef', 'carga-onibus', TIME '07:15', 'I'),
    ('carga-rota-centro', 5, 'carga-escola-ef', 'carga-onibus', TIME '11:40', 'V'),
    ('carga-rota-centro', 6, 'carga-praca', 'carga-onibus', TIME '12:05', 'V'),
    ('carga-rota-rural', 1, 'carga-rural', 'carga-van', TIME '06:20', 'I'),
    ('carga-rota-rural', 2, 'carga-vilanova', 'carga-van', TIME '06:45', 'I'),
    ('carga-rota-rural', 3, 'carga-escola-ef', 'carga-van', TIME '07:20', 'I'),
    ('carga-rota-infantil', 1, 'carga-flores', 'carga-micro', TIME '12:50', 'I'),
    ('carga-rota-infantil', 2, 'carga-escola-ei', 'carga-micro', TIME '13:10', 'I')
) AS dados(rota, seq, ponto, veiculo, hora, tipo)
JOIN transporte_escolar.rota r ON r.origem_chave = dados.rota
JOIN transporte_escolar.ponto p ON p.origem_chave = dados.ponto
JOIN transporte_escolar.veiculo v ON v.origem_chave = dados.veiculo;

CREATE TEMP TABLE carga_ef ON COMMIT DROP AS
SELECT ref_cod_aluno, row_number() OVER (ORDER BY ref_cod_aluno) AS n
FROM (
    SELECT ref_cod_aluno FROM pmieducar.matricula
    WHERE ativo = 1 AND ano = 2026 AND ref_ref_cod_escola = 2
    ORDER BY ref_cod_aluno
    LIMIT 20
) base;

CREATE TEMP TABLE carga_rural ON COMMIT DROP AS
SELECT ref_cod_aluno, row_number() OVER (ORDER BY ref_cod_aluno) AS n
FROM (
    SELECT ref_cod_aluno FROM pmieducar.matricula
    WHERE ativo = 1 AND ano = 2026 AND ref_ref_cod_escola = 2
    ORDER BY ref_cod_aluno
    OFFSET 20 LIMIT 12
) base;

CREATE TEMP TABLE carga_ei ON COMMIT DROP AS
SELECT ref_cod_aluno, row_number() OVER (ORDER BY ref_cod_aluno) AS n
FROM (
    SELECT ref_cod_aluno FROM pmieducar.matricula
    WHERE ativo = 1 AND ano = 2026 AND ref_ref_cod_escola = 3
    ORDER BY ref_cod_aluno
    LIMIT 6
) base;

INSERT INTO transporte_escolar.aluno_rota (ref_cod_aluno, rota_id, ponto_id, turno, ano, residencia_latitude, residencia_longitude, created_at, updated_at)
SELECT a.ref_cod_aluno, r.id, p.id, 'manha', 2026,
       -29.1710 - ((a.n % 4) * 0.001),
       -51.1810 - ((a.n % 4) * 0.001),
       now(), now()
FROM carga_ef a
JOIN transporte_escolar.rota r ON r.origem_chave = 'carga-rota-centro'
JOIN transporte_escolar.ponto p ON p.origem_chave = CASE
    WHEN a.n <= 8 THEN 'carga-praca'
    WHEN a.n <= 14 THEN 'carga-flores'
    ELSE 'carga-saojose'
END;

INSERT INTO transporte_escolar.aluno_rota (ref_cod_aluno, rota_id, ponto_id, turno, ano, residencia_latitude, residencia_longitude, created_at, updated_at)
SELECT a.ref_cod_aluno, r.id, p.id, 'manha', 2026, -29.1880, -51.2080, now(), now()
FROM carga_rural a
JOIN transporte_escolar.rota r ON r.origem_chave = 'carga-rota-rural'
JOIN transporte_escolar.ponto p ON p.origem_chave = 'carga-rural';

INSERT INTO transporte_escolar.aluno_rota (ref_cod_aluno, rota_id, ponto_id, turno, ano, created_at, updated_at)
SELECT a.ref_cod_aluno, r.id, p.id, 'tarde', 2026, now(), now()
FROM carga_ei a
JOIN transporte_escolar.rota r ON r.origem_chave = 'carga-rota-infantil'
JOIN transporte_escolar.ponto p ON p.origem_chave = 'carga-flores';

INSERT INTO transporte_escolar.viagem (data, rota_id, veiculo_id, motorista_id, monitor_id, sentido, km_inicial, km_final, created_at, updated_at)
SELECT DATE '2026-10-07', r.id, v.id, mo.id, mn.id, 'I', 1200.0, 1214.5, now(), now()
FROM transporte_escolar.rota r
JOIN transporte_escolar.veiculo v ON v.origem_chave = 'carga-onibus'
JOIN transporte_escolar.motorista mo ON mo.origem_chave = 'carga-motorista-cnh'
JOIN transporte_escolar.monitor mn ON mn.origem_chave = 'carga-monitor-lucia'
WHERE r.origem_chave = 'carga-rota-centro';

INSERT INTO transporte_escolar.viagem (data, rota_id, veiculo_id, motorista_id, sentido, km_inicial, created_at, updated_at)
SELECT DATE '2026-10-08', r.id, v.id, mo.id, 'I', 1214.5, now(), now()
FROM transporte_escolar.rota r
JOIN transporte_escolar.veiculo v ON v.origem_chave = 'carga-onibus'
JOIN transporte_escolar.motorista mo ON mo.origem_chave = 'carga-motorista-cnh'
WHERE r.origem_chave = 'carga-rota-centro';

INSERT INTO transporte_escolar.embarque (viagem_id, ref_cod_aluno, tipo, ocorrido_em, latitude, longitude, fila_uuid, fora_da_cerca, created_at, updated_at)
SELECT v.id, a.ref_cod_aluno, 'embarque', TIMESTAMP '2026-10-07 06:45', -29.1601, -51.1901, 'carga-emb-' || a.ref_cod_aluno, a.n = 8, now(), now()
FROM transporte_escolar.viagem v
JOIN transporte_escolar.rota r ON r.id = v.rota_id AND r.origem_chave = 'carga-rota-centro'
JOIN carga_ef a ON a.n <= 8
WHERE v.data = DATE '2026-10-07';

INSERT INTO transporte_escolar.embarque (viagem_id, ref_cod_aluno, tipo, ocorrido_em, fila_uuid, fora_da_cerca, created_at, updated_at)
SELECT v.id, a.ref_cod_aluno, 'desembarque', TIMESTAMP '2026-10-07 07:20', 'carga-des-' || a.ref_cod_aluno, false, now(), now()
FROM transporte_escolar.viagem v
JOIN transporte_escolar.rota r ON r.id = v.rota_id AND r.origem_chave = 'carga-rota-centro'
JOIN carga_ef a ON a.n <= 8
WHERE v.data = DATE '2026-10-07';

INSERT INTO transporte_escolar.ocorrencia (viagem_id, texto, ocorrido_em, fila_uuid)
SELECT v.id, 'Pneu furado na Rua das Flores. A viagem seguiu após a troca.', TIMESTAMP '2026-10-07 06:55', 'carga-ocorrencia-1'
FROM transporte_escolar.viagem v
JOIN transporte_escolar.rota r ON r.id = v.rota_id AND r.origem_chave = 'carga-rota-centro'
WHERE v.data = DATE '2026-10-07';

INSERT INTO transporte_escolar.aviso_familia (embarque_id, ref_cod_aluno, token, texto, created_at, updated_at)
SELECT e.id, e.ref_cod_aluno, 'carga-aviso-' || e.ref_cod_aluno, 'O aluno embarcou na linha do centro às 6h45.', now(), now()
FROM transporte_escolar.embarque e
WHERE e.fila_uuid LIKE 'carga-emb-%'
  AND e.ref_cod_aluno = (SELECT ref_cod_aluno FROM carga_ef WHERE n = 1);

INSERT INTO transporte_escolar.tracado (rota_id, formato, conteudo, km_calculado, created_at, updated_at)
SELECT r.id, 'gpx', '<gpx><trk><trkseg><trkpt lat="-29.16" lon="-51.19"></trkpt><trkpt lat="-29.168" lon="-51.179"></trkpt></trkseg></trk></gpx>', 8.00, now(), now()
FROM transporte_escolar.rota r
WHERE r.origem_chave = 'carga-rota-centro'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.tracado t WHERE t.rota_id = r.id);

INSERT INTO transporte_escolar.processo_grupo (nome, ano, inicio, fim, ativo, situacao, created_at, updated_at)
SELECT 'Rede municipal 2026', 2026, DATE '2026-10-01', DATE '2026-12-15', true, 'aberto', now(), now()
WHERE NOT EXISTS (SELECT 1 FROM transporte_escolar.processo_grupo WHERE nome = 'Rede municipal 2026');

UPDATE transporte_escolar.processo_vaga
SET grupo_id = (SELECT id FROM transporte_escolar.processo_grupo WHERE nome = 'Rede municipal 2026'),
    situacao = 'aberto',
    ativo = true,
    updated_at = now()
WHERE id = 1;

INSERT INTO transporte_escolar.processo_vaga (nome, ano, inicio, fim, ativo, situacao, grupo_id, created_at, updated_at)
SELECT 'Transporte rural 2026', 2026, DATE '2026-10-01', DATE '2026-12-15', false, 'pausado', g.id, now(), now()
FROM transporte_escolar.processo_grupo g
WHERE g.nome = 'Rede municipal 2026'
  AND NOT EXISTS (SELECT 1 FROM transporte_escolar.processo_vaga WHERE nome = 'Transporte rural 2026');

INSERT INTO transporte_escolar.processo_vaga_oferta (processo_id, rota_id, ref_cod_escola, turno, vagas, idade_min, idade_max, series, turmas, created_at, updated_at)
SELECT p.id, r.id, 2, 'manha', 12, 6, 14, '[6,7]', '[3,4]', now(), now()
FROM transporte_escolar.processo_vaga p
JOIN transporte_escolar.rota r ON r.origem_chave = 'carga-rota-rural'
WHERE p.nome = 'Transporte rural 2026'
  AND NOT EXISTS (
      SELECT 1 FROM transporte_escolar.processo_vaga_oferta o
      WHERE o.processo_id = p.id AND o.rota_id = r.id AND o.ref_cod_escola = 2 AND o.turno = 'manha'
  );

DELETE FROM transporte_escolar.solicitacao WHERE endereco LIKE 'Carga demonstracao%';

INSERT INTO transporte_escolar.solicitacao (ano, sequencia, protocolo, nome_aluno, nascimento, nome_responsavel, telefone, endereco, turno, escola_informada, ref_cod_escola, situacao, motivo_situacao, pedido_em, atualizado_em, processo_id, rota_id, oferta_id, ref_cod_serie, ref_cod_turma)
SELECT 2026, seq, pg_temp.proto('TE', 2026, seq), nome, nascimento, responsavel, telefone, endereco, 'manha', 'Aurópolis — Escola de Ensino Fundamental', 2, situacao, motivo, now(), now(),
       (SELECT id FROM transporte_escolar.processo_vaga WHERE id = 1),
       (SELECT id FROM transporte_escolar.rota WHERE origem_chave = 'carga-rota-centro'),
       (SELECT id FROM transporte_escolar.processo_vaga_oferta WHERE processo_id = 1 LIMIT 1),
       6, 3
FROM (VALUES
    (3, 'Clara Nunes Carga', DATE '2016-05-10', 'Marta Nunes', '54999990003', 'Carga demonstracao Rua A, 10', 'deferida', 'Documentos conferidos no setor.'),
    (4, 'Diego Souza Carga', DATE '2015-09-01', 'Paulo Souza', '54999990004', 'Carga demonstracao Rua B, 20', 'indeferida', 'Não há vaga no turno pedido.'),
    (5, 'Eva Lima Carga', DATE '2017-01-18', 'Rita Lima', '54999990005', 'Carga demonstracao Rua C, 30', 'cancelada', NULL)
) AS dados(seq, nome, nascimento, responsavel, telefone, endereco, situacao, motivo);

DELETE FROM transporte_escolar.ordem_servico WHERE solicitante = 'Carga demonstracao';
INSERT INTO transporte_escolar.ordem_servico (veiculo_id, fornecedor_id, preventiva, descricao, peca, mao_obra, valor, data, fonte, protocolo, situacao, solicitante, created_at, updated_at)
SELECT v.id, f.id, dados.prev, dados.descricao, dados.peca, dados.mao, dados.valor, dados.data, 'pnate', pg_temp.proto('OS', 2026, dados.seq), dados.situacao, 'Carga demonstracao', now(), now()
FROM transporte_escolar.veiculo v
JOIN transporte_escolar.fornecedor f ON f.origem_chave = 'carga-oficina'
JOIN (VALUES
    (2, false, 'Troca de pastilha dianteira', 'Pastilha', 180.00, 450.00, DATE '2026-10-08', 'autorizada', 'carga-onibus'),
    (3, true, 'Revisão dos 10 mil km', 'Filtro de óleo', 120.00, 320.00, DATE '2026-10-06', 'em_execucao', 'carga-van'),
    (4, false, 'Alinhamento e balanceamento', NULL, 80.00, 180.00, DATE '2026-10-03', 'concluida', 'carga-onibus'),
    (5, false, 'Funilaria da lateral', NULL, NULL, 900.00, DATE '2026-09-20', 'cancelada', 'carga-micro')
) AS dados(seq, prev, descricao, peca, mao, valor, data, situacao, veiculo) ON v.origem_chave = dados.veiculo;

DELETE FROM transporte_escolar.abastecimento WHERE solicitante = 'Carga demonstracao';
INSERT INTO transporte_escolar.abastecimento (veiculo_id, fornecedor_id, data, litros, valor, km, fonte, protocolo, situacao, litros_autorizados, posto, motorista, solicitante, created_at, updated_at)
SELECT v.id, f.id, dados.data, dados.litros, dados.valor, dados.km, 'pnate', pg_temp.proto('AB', 2026, dados.seq), dados.situacao, dados.autorizados, 'Posto Central', 'Carlos Mendes', 'Carga demonstracao', now(), now()
FROM transporte_escolar.veiculo v
JOIN transporte_escolar.fornecedor f ON f.origem_chave = 'carga-posto'
JOIN (VALUES
    (2, DATE '2026-10-08', 0.00, 0.00, NULL::numeric, 'autorizado', 50.00, 'carga-onibus'),
    (3, DATE '2026-10-05', 48.50, 292.00, 1214.5, 'abastecido', 50.00, 'carga-onibus'),
    (4, DATE '2026-09-28', 0.00, 0.00, NULL::numeric, 'cancelado', 30.00, 'carga-van')
) AS dados(seq, data, litros, valor, km, situacao, autorizados, veiculo) ON v.origem_chave = dados.veiculo;

DELETE FROM transporte_escolar.gasto WHERE descricao LIKE 'Carga:%';
INSERT INTO transporte_escolar.gasto (data, categoria, fonte, valor, descricao, veiculo_id, created_at, updated_at)
SELECT dados.data, dados.categoria, dados.fonte, dados.valor, dados.descricao, v.id, now(), now()
FROM (VALUES
    (DATE '2026-10-02', 'seguro', 'pnate', 860.00, 'Carga: seguro anual do ônibus', 'carga-onibus'),
    (DATE '2026-10-04', 'licenciamento', 'proprio', 180.00, 'Carga: licenciamento da van', 'carga-van'),
    (DATE '2026-10-06', 'passe', 'estadual', 240.00, 'Carga: passe estudantil do mês', NULL)
) AS dados(data, categoria, fonte, valor, descricao, veiculo)
LEFT JOIN transporte_escolar.veiculo v ON v.origem_chave = dados.veiculo;

DELETE FROM transporte_escolar.contrato WHERE descricao LIKE 'Carga:%';
INSERT INTO transporte_escolar.contrato (empresa_id, descricao, valor_mensal, inicio, fim, created_at, updated_at)
SELECT e.id, 'Carga: linha rural terceirizada', 8500.00, DATE '2026-02-01', DATE '2026-12-15', now(), now()
FROM transporte_escolar.empresa e
WHERE e.origem_chave = 'carga-empresa';

DELETE FROM transporte_escolar.manutencao_plano
WHERE veiculo_id IN (SELECT id FROM transporte_escolar.veiculo WHERE origem_chave LIKE 'carga-%');
INSERT INTO transporte_escolar.manutencao_plano (veiculo_id, item, a_cada_km, a_cada_dias, ultima_data)
SELECT v.id, item, km, dias, DATE '2026-09-01'
FROM transporte_escolar.veiculo v
JOIN (VALUES
    ('carga-onibus', 'oleo', 10000, 180),
    ('carga-onibus', 'pneu', 40000, NULL),
    ('carga-van', 'revisao', 10000, 180)
) AS dados(veiculo, item, km, dias) ON v.origem_chave = dados.veiculo;

INSERT INTO transporte_escolar.parcela_recurso (ano, mes, fonte, valor, saldo_informado)
SELECT 2026, mes, 'pnate', 15000.00, CASE WHEN mes = 12 THEN 2000.00 ELSE NULL END
FROM generate_series(1, 10) AS mes
ON CONFLICT DO NOTHING;

INSERT INTO transporte_escolar.configuracao (chave, valor)
VALUES
    ('geocerca', '1'),
    ('raio_metros', '150'),
    ('rateio', 'km'),
    ('aquaviario', '0'),
    ('atendimento_setor', 'Transporte Escolar'),
    ('atendimento_endereco', 'Rua da Prefeitura, 100, Centro, Aurópolis'),
    ('atendimento_documentos', 'RG do responsável, certidão de nascimento e comprovante de endereço.'),
    ('atendimento_horario', 'Dias úteis, das 8h às 14h'),
    ('atendimento_latitude', '-29.1685000'),
    ('atendimento_longitude', '-51.1795000')
ON CONFLICT (chave) DO UPDATE SET valor = EXCLUDED.valor;

COMMIT;
