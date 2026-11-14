<?php
declare(strict_types=1);

/**
 * API des agents : les tâches planifiées (Claude) déposent des propositions dans la file
 * « À valider » et lisent les statistiques. Jeton Bearer haché en base (Paramètres).
 */

function api_bearer(): ?string
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($h === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $k => $v) if (strcasecmp($k, 'Authorization') === 0) $h = $v;
    }
    return preg_match('/^Bearer\s+(\S+)$/i', $h, $m) ? $m[1] : null;
}

function api_agents_guard(): void
{
    if (!Agents::verify(api_bearer())) json_out(['error' => 'jeton invalide ou absent'], 401);
}

/** Corps JSON (ou formulaire) de la requête. */
function api_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw !== '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json')) {
        $d = json_decode($raw, true);
        return is_array($d) ? $d : [];
    }
    return $_POST;
}

/** GET /api/health — état public, sans secret. */
function api_health(): void
{
    $db = true;
    try { db()->val('SELECT 1'); } catch (Throwable) { $db = false; }
    json_out([
        'ok' => $db, 'version' => APP_VERSION, 'schema' => (int)setting('schema_version', '0'),
        'php' => PHP_VERSION, 'imap_extension' => extension_loaded('imap'),
        'last_tasks_at' => setting('last_cron_at', null),
    ]);
}

/** POST /api/agents/claim — remet le jeton une seule fois, tant qu'aucun n'existe. */
function api_agents_claim(): void
{
    $t = Agents::claim();
    if ($t === null) json_out(['error' => 'un jeton existe déjà ; régénérez-le dans Paramètres'], 403);
    json_out(['token' => $t, 'note' => 'Conservez-le : il ne sera plus jamais affiché.']);
}

/** GET /api/agents/items?status=&category=&limit= */
function api_agents_items(): void
{
    api_agents_guard();
    json_out(['items' => Agents::items(
        input('status') ?: null,
        input('category') ?: null,
        input_int('limit', 50)
    )]);
}

/** POST /api/agents/items — dépose une proposition dans la file. */
function api_agents_items_create(): void
{
    api_agents_guard();
    $d = api_body();
    $items = isset($d['items']) && is_array($d['items']) ? $d['items'] : [$d];
    $ids = [];
    foreach (array_slice($items, 0, 30) as $it) {
        if (!is_array($it) || trim((string)($it['title'] ?? '')) === '') continue;
        $ids[] = Agents::create($it, mb_substr((string)($d['source'] ?? 'api'), 0, 100));
    }
    if (!$ids) json_out(['error' => 'aucun élément valide (title requis)'], 422);
    json_out(['created' => $ids], 201);
}

/** POST /api/agents/items/{id}/draft — dépose le brouillon d'une réponse attendue. */
function api_agents_items_draft(string $id): void
{
    api_agents_guard();
    if (!Agents::draft((int)$id, api_body())) json_out(['error' => 'élément introuvable ou déjà rédigé'], 409);
    json_out(['ok' => true]);
}

/** GET /api/agents/stats — file, taux de validation, KPI du site. */
function api_agents_stats(): void
{
    api_agents_guard();
    json_out(Agents::stats());
}
