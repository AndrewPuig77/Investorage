<?php
// Low stock is now a filter on the inventory list, with a summary, editing and restock orders.
// Kept so old links and bookmarks still work.
$query = ['stock' => 'low'];
if (is_string($_GET['category'] ?? null) && $_GET['category'] !== '') {
    $query['category'] = $_GET['category'];
}
header("Location: searchInventory.php?" . http_build_query($query));
exit();
