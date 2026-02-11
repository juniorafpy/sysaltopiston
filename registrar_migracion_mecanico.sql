-- Script para registrar la migración de mecanico que ya existe
INSERT INTO migrations (migration, batch)
VALUES ('2026_02_10_000000_create_mecanicos_table', (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations))
ON CONFLICT DO NOTHING;
