<?php
// Builds the link back to the inventory list after an action, keeping the
// user's search, filters, sort and page. Only known filter keys are kept, so
// the posted value can't be used to redirect anywhere else.
function inventoryListUrl($returnQuery, $flashKey = null, $flashText = null) {
    parse_str((string) $returnQuery, $query);
    $allowed = ['search', 'category', 'status', 'stock', 'sort', 'order', 'page'];
    $query = array_filter(array_intersect_key($query, array_flip($allowed)), 'is_string');
    if ($flashKey !== null) {
        $query[$flashKey] = $flashText;
    }
    return '../searchInventory.php' . ($query ? '?' . http_build_query($query) : '');
}
