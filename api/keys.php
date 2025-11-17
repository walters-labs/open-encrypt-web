<?php
include_once __DIR__ . '/../include/db_config.php';
include_once __DIR__ . '/../include/Database.php';
include_once __DIR__ . '/utils.php';

header('Content-Type: application/json');

$db = get_db();

// Validate admin API key first for all operations in keys management
validate_admin_api_key($db);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // List all keys with info (id, prefix, description, active, admin, created_at, rate_limit)
        $rows = $db->fetchAll(
            "SELECT id, api_key, CONCAT(LEFT(api_key, 12), '…') AS key_prefix, description, active, admin, created_at, rate_limit FROM api_keys ORDER BY created_at DESC"
        );
        echo json_encode($rows);
        break;

    case 'POST':
        // Create new API key
        $data = get_json_input();

        $desc = $data['description'] ?? '';
        $is_admin = !empty($data['admin']) ? 1 : 0;
        $rate_limit = isset($data['rate_limit']) ? (int)$data['rate_limit'] : 60; // default rate limit per minute

        // Generate a secure 64 hex char API key (32 bytes)
        $key = bin2hex(random_bytes(32));

        $ok = $db->execute(
            "INSERT INTO api_keys (api_key, description, active, admin, rate_limit) VALUES (?, ?, TRUE, ?, ?)",
            [$key, $desc, $is_admin, $rate_limit],
            "ssis"
        );

        if (!$ok) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create API key']);
            exit;
        }

        $id = $db->fetchOne("SELECT LAST_INSERT_ID() AS id")['id'];

        $new_key = $db->fetchOne(
            "SELECT id, api_key, description, active, admin, created_at, rate_limit FROM api_keys WHERE id = ?",
            [$id],
            "i"
        );

        echo json_encode($new_key);
        break;

    case 'PATCH':
        // Update key active status and/or rate limit
        $data = get_json_input();

        $id = $data['id'] ?? null;
        $active = isset($data['active']) ? ($data['active'] ? 1 : 0) : null;
        $rate_limit = isset($data['rate_limit']) ? (int)$data['rate_limit'] : null;

        if (!$id || !is_int($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id parameter']);
            exit;
        }
        if ($active === null && $rate_limit === null) {
            http_response_code(400);
            echo json_encode(['error' => 'Nothing to update']);
            exit;
        }

        if ($active !== null && $rate_limit !== null) {
            $ok = $db->execute(
                "UPDATE api_keys SET active = ?, rate_limit = ? WHERE id = ?",
                [$active, $rate_limit, $id],
                "isi"
            );
        } else if ($active !== null) {
            $ok = $db->execute(
                "UPDATE api_keys SET active = ? WHERE id = ?",
                [$active, $id],
                "ii"
            );
        } else {
            $ok = $db->execute(
                "UPDATE api_keys SET rate_limit = ? WHERE id = ?",
                [$rate_limit, $id],
                "ii"
            );
        }

        if (!$ok) {
            http_response_code(404);
            echo json_encode(['error' => 'API key not found or update failed']);
            exit;
        }

        $updated_key = $db->fetchOne(
            "SELECT id, description, active, admin, created_at, rate_limit FROM api_keys WHERE id = ?",
            [$id],
            "i"
        );

        echo json_encode($updated_key);
        break;

    case 'DELETE':
        // Delete API key by id
        $data = get_json_input();

        $id = $data['id'] ?? null;

        if (!$id || !is_int($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid id parameter']);
            exit;
        }

        $ok = $db->execute(
            "DELETE FROM api_keys WHERE id = ?",
            [$id],
            "i"
        );

        if (!$ok) {
            http_response_code(404);
            echo json_encode(['error' => 'API key not found or deletion failed']);
            exit;
        }

        echo json_encode(['deleted_id' => $id]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
