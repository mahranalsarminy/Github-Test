<?php
$stmt = $pdo->query("SELECT header_menu_id FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
$header_menu_id = $site_settings['header_menu_id'];
?>
<?php if ($header_menu_id): ?>
<nav role="navigation">
    <ul>
        <?php
        $stmt = $pdo->prepare("SELECT name, url FROM menu_items WHERE menu_id = ? ORDER BY sort_order");
        $stmt->execute([$header_menu_id]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<li><a href="' . $row['url'] . '">' . $row['name'] . '</a></li>';
        }
        ?>
    </ul>
</nav>
<?php endif; ?>