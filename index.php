<?php
/**
 * Homepage - FeyFay Media
 * Breaking ticker, featured hero, latest grid, category sections, sidebar
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';
// Optimized: minimal queries for above-the-fold data
$breaking = get_breaking_news($pdo, 5);
$featured = get_featured_post($pdo);
if (!$featured) {
    $latest_one = get_posts($pdo, 1, 0, null);
    $featured = $latest_one[0] ?? null;
}
$latest = get_posts($pdo, 6, $featured ? 1 : 0, null);
$categories = get_categories($pdo);
$category_ids = array_slice(array_column($categories, 'id'), 0, 4);
$posts_by_category = get_posts_by_category_ids($pdo, $category_ids, 3);
$settings = get_settings($pdo);
$ads_homepage = $settings['ads_homepage'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breaking news ticker -->
<?php if (!empty($breaking)): ?>
<div class="breaking-bar">
    <div class="container">
        <span class="breaking-label">Breaking</span>
        <div class="breaking-ticker">
            <?php foreach ($breaking as $i => $b): ?>
            <a href="<?php echo base_url('post.php?slug=' . e($b['slug'])); ?>"><?php echo e($b['title']); ?></a><?php if ($i < count($breaking) - 1): ?> &bull; <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Hero / Featured -->
<?php if ($featured): ?>
<section class="hero featured-section">
    <div class="container">
        <article class="featured-article">
            <a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>" class="featured-image-wrap">
                <?php if (!empty($featured['image'])): ?>
                <img src="<?php echo base_url($featured['image']); ?>" alt="<?php echo e($featured['title']); ?>" loading="eager" fetchpriority="high">
                <?php else: ?>
                <div class="featured-placeholder"></div>
                <?php endif; ?>
            </a>
            <div class="featured-body">
                <a href="<?php echo base_url('category.php?slug=' . e($featured['category_slug'])); ?>" class="cat-badge"><?php echo e($featured['category_name']); ?></a>
                <h1 class="featured-title"><a href="<?php echo base_url('post.php?slug=' . e($featured['slug'])); ?>"><?php echo e($featured['title']); ?></a></h1>
                <p class="featured-excerpt"><?php echo e(excerpt($featured['summary'] ?: $featured['content'], 200)); ?></p>
                <div class="featured-meta"><?php echo format_date($featured['created_at']); ?> &middot; <?php echo e($featured['author_name']); ?></div>
            </div>
        </article>
    </div>
</section>
<?php endif; ?>

<div class="container content-wrap">
    <div class="content-main">
        <!-- Latest news grid -->
        <section class="section latest-section">
            <h2 class="section-heading">Latest News</h2>
            <?php if (empty($latest) && !$featured): ?>
            <p class="no-posts">No articles yet. Check back soon.</p>
            <?php elseif (!empty($latest)): ?>
            <div class="posts-grid">
                <?php foreach ($latest as $post): ?>
                <article class="post-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <div class="post-card-meta-top">
                            <a href="<?php echo base_url('category.php?slug=' . e($post['category_slug'])); ?>" class="cat-badge"><?php echo e($post['category_name']); ?></a>
                            <?php if (!empty($post['is_sponsored'])): ?><span class="sponsored-badge">Sponsored</span><?php endif; ?>
                        </div>
                        <h3><a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a></h3>
                        <p class="excerpt"><?php echo e(excerpt($post['summary'] ?: $post['content'], 120)); ?></p>
                        <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($ads_homepage): ?>
        <section class="section ads-section">
            <div class="ads ads-homepage">
                <div class="ad-placeholder"><?php echo $ads_homepage; ?></div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Category sections (single optimized query) -->
        <?php foreach (array_slice($categories, 0, 4) as $cat): 
            $cat_posts = $posts_by_category[$cat['id']] ?? [];
            if (empty($cat_posts)) continue;
        ?>
        <section class="section category-section">
            <h2 class="section-heading"><a href="<?php echo base_url('category.php?slug=' . e($cat['slug'])); ?>"><?php echo e($cat['name']); ?></a></h2>
            <div class="posts-grid three-col">
                <?php foreach ($cat_posts as $post): ?>
                <article class="post-card">
                    <a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>" class="post-card-img">
                        <?php if (!empty($post['image'])): ?>
                        <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="lazy">
                        <?php else: ?>
                        <div class="img-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="post-card-body">
                        <?php if (!empty($post['is_sponsored'])): ?><span class="sponsored-badge">Sponsored</span><?php endif; ?>
                        <h3><a href="<?php echo base_url('post.php?slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a></h3>
                        <span class="meta"><?php echo format_date($post['created_at']); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </div>
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
