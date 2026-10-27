# FormiCash

App de finanzas personales (ingresos y gastos en COP) con PHP + MySQL. UI fintech didáctica: dashboard, movimientos, categorías y resumen.

## Requisitos

- XAMPP (Apache + MySQL)
- PHP con extensión `mysqli`

## Instalación

1. Carpeta en `htdocs/FormiCash`.
2. Importar la base de datos:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root < bd/formicash.sql
```

3. Abrir: [http://localhost/FormiCash/](http://localhost/FormiCash/)

## Usuario demo

- **Email:** admin@demo.com  
- **Contraseña:** Admin123!

## Flujo UX

Login → Dashboard (métricas del mes) → Movimientos → Resumen por categoría.

Al registrarte se siembran categorías de ingreso/gasto. No se puede cambiar el tipo de una categoría si ya tiene movimientos.

## Caché

Estilo Strelee (archivos en `cache/`, TTL 4 h):

- Lecturas de dashboard, movimientos, resumen y categorías por usuario/mes.
- Parche inteligente al crear/editar/eliminar (write-through en listas; rebuild de dash/resumen solo del mes afectado).
- Estáticos con `.htaccess` (CSS/SVG/imágenes).

## Estructura

```
FormiCash/
├── bd/formicash.sql
├── cache/            # caché de datos (denegado por Apache)
├── includes/
│   ├── cache.php
│   ├── conexion.php
│   └── funciones.php
├── css/estilos.css
├── img/
├── login.php / registro.php / logout.php
├── index.php / dashboard.php
├── movimientos.php / movimiento_form.php
├── categorias.php / resumen.php / perfil.php
```
