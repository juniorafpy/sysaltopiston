# Guia de Despliegue en DigitalOcean

## Opcion 1: DigitalOcean App Platform (Recomendado)

### Requisitos
- Cuenta en DigitalOcean
- doctl CLI (opcional)
- Docker instalado

### Pasos

1. **Login en DigitalOcean Container Registry**
```bash
# Instalar doctl: https://docs.digitalocean.com/reference/doctl/how-to/install/
doctl auth init
```

2. **Build de la imagen de produccion**
```bash
# Desde la raiz del proyecto:
docker build -f Dockerfile.prod -t altopiston:latest .
```

3. **Tag y Push a DOCR**
```bash
# Login al registry
# DigitalOcean te da el registry URL en tu panel
# Ejemplo: registry.digitalocean.com/tu-registry

# Tag
docker tag altopiston:latest registry.digitalocean.com/tu-registry/altopiston:latest

# Push
docker push registry.digitalocean.com/tu-registry/altopiston:latest
```

4. **Deploy con app.yaml**
```bash
# Crear la app
doctl apps create --spec app.yaml

# O actualizar si ya existe:
# doctl apps update <APP_ID> --spec app.yaml
```

5. **Configurar variables de entorno** (opcional, ya estan en app.yaml)
- Ir al panel de DigitalOcean -> Apps -> tu app -> Settings
- Verificar las variables de entorno

6. **Configurar dominio**
- Ir a Settings -> Domains
- Agregar tu dominio
- Configurar DNS apuntando a DigitalOcean

---

## Opcion 2: DigitalOcean Droplet (VPS) con Docker

### Requisitos
- Droplet con Ubuntu 22.04
- Docker y Docker Compose instalados

### Pasos

1. **Crear Droplet** (minimo 2GB RAM, 1 vCPU)

2. **Instalar Docker y Docker Compose**
```bash
sudo apt update
sudo apt install -y docker.io docker-compose
sudo systemctl enable docker
sudo systemctl start docker
```

3. **Copiar el proyecto**
```bash
# Desde tu PC:
scp -r . root@TU_IP:/var/www/altopiston

# O clonar desde Git:
# git clone https://github.com/tuusuario/sysaltopiston.git /var/www/altopiston
```

4. **Configurar .env**
```bash
cd /var/www/altopiston
cp .env.example .env
nano .env
# Editar DB_HOST, DB_PASSWORD, APP_URL, etc.
```

5. **Deploy con docker-compose**
```bash
# Construir e iniciar
docker-compose -f docker-compose.prod.yml up -d

# Ver logs
docker-compose -f docker-compose.prod.yml logs -f

# Ejecutar migraciones
docker-compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

6. **SSL con Certbot (Nginx)**
```bash
# Instalar certbot
docker-compose -f docker-compose.prod.yml exec app apk add certbot
# O usar Nginx proxy con letsencrypt companion
```

---

## Opcion 3: Docker Compose local (testing)

```bash
# Desarrollo
docker-compose up -d

# Produccion (test local)
docker-compose -f docker-compose.prod.yml up -d
```

---

## Notas importantes

### Storage / Archivos subidos
- **App Platform**: Usar DigitalOcean Spaces (S3 compatible) o almacenar en DB
- **Droplet**: Los archivos se guardan en el volumen `app_storage`

### Base de datos
- **App Platform**: Usar Managed PostgreSQL (incluido en app.yaml)
- **Droplet**: PostgreSQL container incluido en docker-compose.prod.yml

### Cache/Sesiones
- **App Platform**: Managed Redis (incluido en app.yaml)
- **Droplet**: Redis container incluido

### Health Check
- El endpoint `/health` debe responder 200 OK
- Si no tenes ruta health, agregar en routes/web.php:
```php
Route::get('/health', fn() => response()->json(['status' => 'ok']));
```

### Migraciones
- En App Platform: se ejecutan automaticamente en el entrypoint
- En Droplet: ejecutar manualmente con `docker-compose exec app php artisan migrate --force`

### Filament
- Despues del deploy, ejecutar: `php artisan filament:upgrade`

---

## Troubleshooting

### Error: Permission denied storage
```bash
# En el contenedor:
chmod -R 775 /var/www/storage
chown -R www-data:www-data /var/www/storage
```

### Error: APP_KEY not set
```bash
# En el contenedor:
php artisan key:generate
```

### Error: mix manifest not found
- Asegurarse de ejecutar `npm run build` en el Dockerfile

### Error: PostgreSQL no conecta
- Verificar DB_HOST, DB_PORT, DB_PASSWORD en .env
- En Docker, usar `db` como DB_HOST (nombre del servicio)
