-- Registrar las migraciones que ya fueron ejecutadas
INSERT INTO migrations (migration, batch)
VALUES ('2025_11_15_100000_create_clientes_table', 1)
ON CONFLICT DO NOTHING;
