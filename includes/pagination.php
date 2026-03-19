<?php
/**
 * Pagination helper - FeyFay Media
 * Expects: $current_page, $total_pages, $base_url (e.g. "category.php?slug=tech&page=")
 */
if (!isset($current_page) || !isset($total_pages) || $total_pages <= 1) return;
$base_url = $base_url ?? '?page=';
?>
<nav class="pagination" aria-label="Pagination">
    <ul class="pagination-list">
        <?php if ($current_page > 1): ?>
        <li><a href="<?php echo e($base_url . ($current_page - 1)); ?>" class="pagination-prev">Previous</a></li>
        <?php endif; ?>
        <?php
        $start = max(1, $current_page - 2);
        $end = min($total_pages, $current_page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
        <li>
            <?php if ($i == $current_page): ?>
            <span class="pagination-current"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="<?php echo e($base_url . $i); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        </li>
        <?php endfor; ?>
        <?php if ($current_page < $total_pages): ?>
        <li><a href="<?php echo e($base_url . ($current_page + 1)); ?>" class="pagination-next">Next</a></li>
        <?php endif; ?>
    </ul>
    <p class="pagination-info">Page <?php echo e($current_page); ?> of <?php echo e($total_pages); ?></p>
</nav>
