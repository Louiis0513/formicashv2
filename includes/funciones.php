<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/cache.php';

function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function usuarioLogueado() {
    return isset($_SESSION['usuario']['id']);
}

function requiereUsuario() {
    if (!usuarioLogueado()) {
        guardarMensaje('Debes iniciar sesión para continuar.', 'error');
        header('Location: login.php');
        exit;
    }
}

function guardarMensaje($texto, $tipo = 'ok') {
    $_SESSION['flash'] = ['texto' => $texto, 'tipo' => $tipo];
}

function obtenerMensaje() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $msg;
}

function formatoPesos($n) {
    return '$' . number_format((float) $n, 0, ',', '.');
}

function idUsuario() {
    return (int) ($_SESSION['usuario']['id'] ?? 0);
}

function nombreMes($ym) {
    static $meses = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];
    $ts = strtotime($ym . '-01');
    if (!$ts) {
        return $ym;
    }
    $m = (int) date('n', $ts);
    $y = date('Y', $ts);
    return ucfirst($meses[$m]) . ' ' . $y;
}

function iconoSvg($nombre, $clase = 'nav-icon') {
    $src = 'img/' . $nombre . '.svg';
    return '<img class="' . e($clase) . '" src="' . e($src) . '" alt="" width="20" height="20">';
}

function mesValido(?string $mes): string
{
    $mes = $mes ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
        return date('Y-m');
    }
    return $mes;
}

function mesDeFecha(string $fecha): string
{
    return substr($fecha, 0, 7);
}

/** Categorías por defecto al crear cuenta (mismas del seed demo). */
function categoriasSemilla(): array
{
    return [
        ['Alimentación', 'gasto'],
        ['Transporte', 'gasto'],
        ['Vivienda', 'gasto'],
        ['Servicios', 'gasto'],
        ['Salud', 'gasto'],
        ['Entretenimiento', 'gasto'],
        ['Educación', 'gasto'],
        ['Compras', 'gasto'],
        ['Salario', 'ingreso'],
        ['Freelance', 'ingreso'],
        ['Transferencias', 'ingreso'],
        ['Otros ingresos', 'ingreso'],
    ];
}

function sembrarCategoriasUsuario(mysqli $db, int $idUsuario): void
{
    $stmt = $db->prepare('INSERT INTO categoria (nombre, tipo, id_usuario) VALUES (?, ?, ?)');
    foreach (categoriasSemilla() as [$nombre, $tipo]) {
        $stmt->bind_param('ssi', $nombre, $tipo, $idUsuario);
        $stmt->execute();
    }
    $stmt->close();
    cacheParcheCategoriasUsuario($db, $idUsuario);
}

/* ——— Claves de caché por usuario ——— */

function cacheKeyCategorias(int $uid): string
{
    return 'u' . $uid . '_categorias';
}

function cacheKeyDash(int $uid, string $mes): string
{
    return 'u' . $uid . '_dash_' . $mes;
}

function cacheKeyMovs(int $uid, string $mes, string $tipo = ''): string
{
    $suf = ($tipo === 'ingreso' || $tipo === 'gasto') ? $tipo : 'all';
    return 'u' . $uid . '_movs_' . $mes . '_' . $suf;
}

function cacheKeyResumen(int $uid, string $mes): string
{
    return 'u' . $uid . '_resumen_' . $mes;
}

/* ——— Lecturas cacheadas ——— */

/**
 * @return list<array{id_categoria:int|string,nombre:string,tipo:string,usos:int|string}>
 */
function obtenerCategoriasUsuario(mysqli $db, int $idUsuario): array
{
    $key = cacheKeyCategorias($idUsuario);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }
    $stmt = $db->prepare(
        'SELECT c.id_categoria, c.nombre, c.tipo,
                (SELECT COUNT(*) FROM movimiento m WHERE m.id_categoria = c.id_categoria AND m.id_usuario = c.id_usuario) AS usos
         FROM categoria c
         WHERE c.id_usuario = ?
         ORDER BY c.tipo, c.nombre'
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    cacheSet($key, $rows, CACHE_TTL_CATEGORIAS);
    return $rows;
}

/**
 * Categorías simples para formularios (sin conteo de usos).
 * @return list<array{id_categoria:int|string,nombre:string,tipo:string}>
 */
function obtenerCategoriasForm(mysqli $db, int $idUsuario): array
{
    $cats = obtenerCategoriasUsuario($db, $idUsuario);
    $out = [];
    foreach ($cats as $c) {
        $out[] = [
            'id_categoria' => $c['id_categoria'],
            'nombre' => $c['nombre'],
            'tipo' => $c['tipo'],
        ];
    }
    return $out;
}

/**
 * Totales + últimos movimientos del mes (dashboard).
 * @return array{ingresos:float,gastos:float,recientes:list<array<string,mixed>>}
 */
function obtenerDashboardMes(mysqli $db, int $idUsuario, string $mes): array
{
    $mes = mesValido($mes);
    $key = cacheKeyDash($idUsuario, $mes);
    $cached = cacheGet($key);
    if (is_array($cached) && isset($cached['ingresos'], $cached['gastos'], $cached['recientes'])) {
        return $cached;
    }

    $ingresos = 0.0;
    $gastos = 0.0;
    $stmt = $db->prepare(
        "SELECT tipo, COALESCE(SUM(monto), 0) AS total
         FROM movimiento
         WHERE id_usuario = ? AND DATE_FORMAT(fecha, '%Y-%m') = ?
         GROUP BY tipo"
    );
    $stmt->bind_param('is', $idUsuario, $mes);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if ($row['tipo'] === 'ingreso') {
            $ingresos = (float) $row['total'];
        } else {
            $gastos = (float) $row['total'];
        }
    }
    $stmt->close();

    $stmt = $db->prepare(
        "SELECT m.id_movimiento, m.descripcion, m.monto, m.tipo, m.fecha, m.id_categoria, c.nombre AS categoria
         FROM movimiento m
         INNER JOIN categoria c ON c.id_categoria = m.id_categoria
         WHERE m.id_usuario = ? AND DATE_FORMAT(m.fecha, '%Y-%m') = ?
         ORDER BY m.fecha DESC, m.id_movimiento DESC
         LIMIT 8"
    );
    $stmt->bind_param('is', $idUsuario, $mes);
    $stmt->execute();
    $recientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $data = [
        'ingresos' => $ingresos,
        'gastos' => $gastos,
        'recientes' => $recientes,
    ];
    cacheSet($key, $data, CACHE_TTL_DASH);
    return $data;
}

/**
 * @return list<array<string,mixed>>
 */
function obtenerMovimientosMes(mysqli $db, int $idUsuario, string $mes, string $tipoFiltro = ''): array
{
    $mes = mesValido($mes);
    if (!in_array($tipoFiltro, ['', 'ingreso', 'gasto'], true)) {
        $tipoFiltro = '';
    }
    $key = cacheKeyMovs($idUsuario, $mes, $tipoFiltro);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }

    $sql = "SELECT m.id_movimiento, m.descripcion, m.monto, m.tipo, m.fecha, m.id_categoria, c.nombre AS categoria
            FROM movimiento m
            INNER JOIN categoria c ON c.id_categoria = m.id_categoria
            WHERE m.id_usuario = ? AND DATE_FORMAT(m.fecha, '%Y-%m') = ?";
    $types = 'is';
    $params = [$idUsuario, $mes];
    if ($tipoFiltro !== '') {
        $sql .= ' AND m.tipo = ?';
        $types .= 's';
        $params[] = $tipoFiltro;
    }
    $sql .= ' ORDER BY m.fecha DESC, m.id_movimiento DESC';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    cacheSet($key, $rows, CACHE_TTL_DASH);
    return $rows;
}

/**
 * @return list<array{nombre:string,tipo:string,total:float|string,id_categoria?:int|string}>
 */
function obtenerResumenMes(mysqli $db, int $idUsuario, string $mes): array
{
    $mes = mesValido($mes);
    $key = cacheKeyResumen($idUsuario, $mes);
    $cached = cacheGet($key);
    if (is_array($cached)) {
        return $cached;
    }

    $stmt = $db->prepare(
        "SELECT c.id_categoria, c.nombre, c.tipo, COALESCE(SUM(m.monto), 0) AS total
         FROM movimiento m
         INNER JOIN categoria c ON c.id_categoria = m.id_categoria
         WHERE m.id_usuario = ? AND DATE_FORMAT(m.fecha, '%Y-%m') = ?
         GROUP BY c.id_categoria, c.nombre, c.tipo
         ORDER BY c.tipo, total DESC"
    );
    $stmt->bind_param('is', $idUsuario, $mes);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    cacheSet($key, $rows, CACHE_TTL_DASH);
    return $rows;
}

/**
 * Carga un movimiento con nombre de categoría (para parches / edición).
 */
function cargarMovimientoUsuario(mysqli $db, int $idMovimiento, int $idUsuario): ?array
{
    if ($idMovimiento <= 0 || $idUsuario <= 0) {
        return null;
    }
    $stmt = $db->prepare(
        'SELECT m.id_movimiento, m.descripcion, m.monto, m.tipo, m.fecha, m.id_categoria, c.nombre AS categoria
         FROM movimiento m
         INNER JOIN categoria c ON c.id_categoria = m.id_categoria
         WHERE m.id_movimiento = ? AND m.id_usuario = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $idMovimiento, $idUsuario);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/* ——— Parche inteligente (write-through / rebuild si presente) ——— */

function cacheListaQuitarMovimiento(array $lista, int $idMovimiento): array
{
    $out = [];
    foreach ($lista as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ((int) ($row['id_movimiento'] ?? 0) === $idMovimiento) {
            continue;
        }
        $out[] = $row;
    }
    return $out;
}

/** Inserta/reemplaza en lista ordenada por fecha DESC, id DESC. */
function cacheListaUpsertMovimiento(array $lista, array $mov): array
{
    $id = (int) ($mov['id_movimiento'] ?? 0);
    $lista = cacheListaQuitarMovimiento($lista, $id);
    $lista[] = $mov;
    usort($lista, static function (array $a, array $b): int {
        $fa = (string) ($a['fecha'] ?? '');
        $fb = (string) ($b['fecha'] ?? '');
        if ($fa !== $fb) {
            return $fb <=> $fa;
        }
        return ((int) ($b['id_movimiento'] ?? 0)) <=> ((int) ($a['id_movimiento'] ?? 0));
    });
    return $lista;
}

function cacheRebuildDashIfPresent(mysqli $db, int $idUsuario, string $mes): void
{
    $key = cacheKeyDash($idUsuario, $mes);
    if (cachePeek($key) === null) {
        return;
    }
    cacheForget($key);
    obtenerDashboardMes($db, $idUsuario, $mes);
}

function cacheRebuildResumenIfPresent(mysqli $db, int $idUsuario, string $mes): void
{
    $key = cacheKeyResumen($idUsuario, $mes);
    if (cachePeek($key) === null) {
        return;
    }
    cacheForget($key);
    obtenerResumenMes($db, $idUsuario, $mes);
}

function cacheRebuildCategoriasUsuario(mysqli $db, int $idUsuario): void
{
    cacheForget(cacheKeyCategorias($idUsuario));
    obtenerCategoriasUsuario($db, $idUsuario);
}

/**
 * Tras create/update/delete de movimiento: parchea listas del mes y rebuild dash/resumen si existen.
 * $antes / $despues = filas con fecha, tipo, id_movimiento, etc. null = no existía / eliminado.
 */
function cacheParcheMovimiento(mysqli $db, int $idUsuario, ?array $antes, ?array $despues): void
{
    $idUsuario = (int) $idUsuario;
    if ($idUsuario <= 0) {
        return;
    }

    $meses = [];
    if ($antes && !empty($antes['fecha'])) {
        $meses[mesDeFecha((string) $antes['fecha'])] = true;
    }
    if ($despues && !empty($despues['fecha'])) {
        $meses[mesDeFecha((string) $despues['fecha'])] = true;
    }
    if (!$meses) {
        return;
    }

    $idMov = (int) (($despues['id_movimiento'] ?? null) ?: ($antes['id_movimiento'] ?? 0));

    foreach (array_keys($meses) as $mes) {
        foreach (['all', 'ingreso', 'gasto'] as $filtro) {
            $key = cacheKeyMovs($idUsuario, $mes, $filtro === 'all' ? '' : $filtro);
            cachePatchIfPresent($key, static function ($data) use ($antes, $despues, $idMov, $mes, $filtro) {
                if (!is_array($data)) {
                    return $data;
                }
                $data = cacheListaQuitarMovimiento($data, $idMov);
                if ($despues && mesDeFecha((string) $despues['fecha']) === $mes) {
                    $tipo = (string) ($despues['tipo'] ?? '');
                    if ($filtro === 'all' || $filtro === $tipo) {
                        $fila = $despues;
                        // Listas de movimientos no necesitan id_categoria en UI, pero no molesta.
                        $data = cacheListaUpsertMovimiento($data, $fila);
                    }
                }
                return $data;
            }, CACHE_TTL_DASH);
        }
        cacheRebuildDashIfPresent($db, $idUsuario, $mes);
        cacheRebuildResumenIfPresent($db, $idUsuario, $mes);
    }

    // usos de categorías puede cambiar
    cacheRebuildCategoriasUsuario($db, $idUsuario);
}

/**
 * Tras create/edit/delete de categoría: refresca listado y parchea nombres en listas/resúmenes.
 */
function cacheParcheCategoriasUsuario(
    mysqli $db,
    int $idUsuario,
    ?int $idCat = null,
    ?string $nombreNuevo = null,
    bool $eliminada = false
): void {
    $idUsuario = (int) $idUsuario;
    if ($idUsuario <= 0) {
        return;
    }

    cacheRebuildCategoriasUsuario($db, $idUsuario);

    if ($idCat && $nombreNuevo !== null && !$eliminada) {
        $id = (int) $idCat;
        $nom = (string) $nombreNuevo;
        $prefix = 'u' . $idUsuario . '_movs_';
        foreach (cacheKeysByPrefix($prefix) as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (!is_array($data)) {
                    return $data;
                }
                foreach ($data as $i => $row) {
                    if (is_array($row) && (int) ($row['id_categoria'] ?? 0) === $id) {
                        $data[$i]['categoria'] = $nom;
                    }
                }
                return $data;
            }, CACHE_TTL_DASH);
        }
        foreach (cacheKeysByPrefix('u' . $idUsuario . '_dash_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (!is_array($data) || !isset($data['recientes']) || !is_array($data['recientes'])) {
                    return $data;
                }
                foreach ($data['recientes'] as $i => $row) {
                    if (is_array($row) && (int) ($row['id_categoria'] ?? 0) === $id) {
                        $data['recientes'][$i]['categoria'] = $nom;
                    }
                }
                return $data;
            }, CACHE_TTL_DASH);
        }
        foreach (cacheKeysByPrefix('u' . $idUsuario . '_resumen_') as $key) {
            cachePatchIfPresent($key, static function ($data) use ($id, $nom) {
                if (!is_array($data)) {
                    return $data;
                }
                foreach ($data as $i => $row) {
                    if (is_array($row) && (int) ($row['id_categoria'] ?? 0) === $id) {
                        $data[$i]['nombre'] = $nom;
                    }
                }
                return $data;
            }, CACHE_TTL_DASH);
        }
    }

    if ($eliminada && $idCat) {
        // Sin movimientos asociados; dash/movs/resumen no deberían referenciarla.
        // Por seguridad no wipeamos meses enteros.
    }
}
