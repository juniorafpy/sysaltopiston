-- Crear tabla cobros (cabecera)
CREATE TABLE IF NOT EXISTS cobros (
    cod_cobro BIGSERIAL PRIMARY KEY,
    cod_cliente BIGINT NOT NULL,
    cod_apertura BIGINT NOT NULL,
    fecha_cobro DATE NOT NULL,
    monto_total NUMERIC(15, 2) NOT NULL,
    observaciones TEXT,
    usuario_alta BIGINT NOT NULL,
    fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_cobros_cliente FOREIGN KEY (cod_cliente) REFERENCES personas(cod_persona) ON DELETE CASCADE,
    CONSTRAINT fk_cobros_apertura FOREIGN KEY (cod_apertura) REFERENCES aperturas_caja(cod_apertura) ON DELETE CASCADE,
    CONSTRAINT fk_cobros_usuario FOREIGN KEY (usuario_alta) REFERENCES users(id)
);

-- Crear tabla cobros_detalle (facturas/cuotas cobradas)
CREATE TABLE IF NOT EXISTS cobros_detalle (
    cod_cobro_detalle BIGSERIAL PRIMARY KEY,
    cod_cobro BIGINT NOT NULL,
    cod_factura BIGINT NOT NULL,
    numero_cuota INTEGER NOT NULL,
    monto_cuota NUMERIC(15, 2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_cobros_detalle_cobro FOREIGN KEY (cod_cobro) REFERENCES cobros(cod_cobro) ON DELETE CASCADE,
    CONSTRAINT fk_cobros_detalle_factura FOREIGN KEY (cod_factura) REFERENCES facturas(cod_factura) ON DELETE CASCADE
);

-- Crear tabla cobros_formas_pago (formas de pago utilizadas)
CREATE TABLE IF NOT EXISTS cobros_formas_pago (
    cod_forma_pago BIGSERIAL PRIMARY KEY,
    cod_cobro BIGINT NOT NULL,
    tipo_transaccion VARCHAR(20) NOT NULL CHECK (tipo_transaccion IN ('efectivo', 'tarjeta_credito', 'tarjeta_debito', 'cheque', 'transferencia')),
    monto NUMERIC(15, 2) NOT NULL,
    cod_entidad_bancaria BIGINT,
    numero_voucher VARCHAR(50),
    numero_cheque VARCHAR(50),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    CONSTRAINT fk_cobros_fp_cobro FOREIGN KEY (cod_cobro) REFERENCES cobros(cod_cobro) ON DELETE CASCADE,
    CONSTRAINT fk_cobros_fp_banco FOREIGN KEY (cod_entidad_bancaria) REFERENCES entidades_bancarias(cod_entidad_bancaria) ON DELETE SET NULL
);

-- Crear índices para mejorar performance
CREATE INDEX IF NOT EXISTS idx_cobros_cliente ON cobros(cod_cliente);
CREATE INDEX IF NOT EXISTS idx_cobros_apertura ON cobros(cod_apertura);
CREATE INDEX IF NOT EXISTS idx_cobros_fecha ON cobros(fecha_cobro);
CREATE INDEX IF NOT EXISTS idx_cobros_detalle_cobro ON cobros_detalle(cod_cobro);
CREATE INDEX IF NOT EXISTS idx_cobros_detalle_factura ON cobros_detalle(cod_factura);
CREATE INDEX IF NOT EXISTS idx_cobros_fp_cobro ON cobros_formas_pago(cod_cobro);
