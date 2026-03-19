<?php
/**
 * Single article - FeyFay Media
 * Title, image, category, author, date, content, related posts, comments
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if ($slug === '') {
    include __DIR__ . '/404.php';
    exit;
}

$post = get_post($pdo, $slug, true);
if (!$post) {
    include __DIR__ . '/404.php';
    exit;
}

// SEO: meta, canonical, Open Graph, Twitter Card
$page_title = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
$meta_description = !empty($post['meta_description']) ? $post['meta_description'] : excerpt($post['summary'] ?: $post['content'], 160);
$canonical_url = base_url('post.php?slug=' . rawurlencode($post['slug']));
$og_title = $page_title;
$og_description = $meta_description;
$og_image = !empty($post['image']) ? base_url($post['image']) : '';
$og_type = 'article';

$related = get_related_posts($pdo, $post['id'], $post['category_id'], 3);
$comments = get_post_comments($pdo, $post['id']);
$tags = get_tags_for_post($pdo, $post['id']);
$settings = get_settings($pdo);
$ads_article = $settings['ads_article'] ?? '';
$share_url = $canonical_url;
$share_title = rawurlencode($post['title']);
$share_text = rawurlencode($meta_description);

require_once __DIR__ . '/includes/header.php';
?>

<article class="single-article">
    <div class="container article-inner">
        <header class="article-header">
            <div class="article-header-top">
                <a href="<?php echo base_url('category.php?slug=' . e($post['category_slug'])); ?>" class="cat-badge"><?php echo e($post['category_name']); ?></a>
                <?php if (!empty($post['is_sponsored'])): ?><span class="sponsored-badge">Sponsored</span><?php endif; ?>
            </div>
            <h1 class="article-title"><?php echo e($post['title']); ?></h1>
            <div class="article-meta">
                <span><?php echo e($post['author_name']); ?></span>
                <span><?php echo format_date($post['created_at']); ?></span>
                <?php if ($post['views'] > 0): ?><span><?php echo (int)$post['views']; ?> views</span><?php endif; ?>
            </div>
        </header>
        <?php if (!empty($post['image'])): ?>
        <div class="article-image">
            <img src="<?php echo base_url($post['image']); ?>" alt="<?php echo e($post['title']); ?>" loading="eager" fetchpriority="high">
        </div>
        <?php endif; ?>
        <?php if ($ads_article): ?>
        <div class="ads ads-article">
            <div class="ad-placeholder"><?php echo $ads_article; ?></div>
        </div>
        <?php endif; ?>
        <div class="article-content">
            <?php if ($post['summary']): ?>
            <p class="article-summary"><?php echo nl2br(e($post['summary'])); ?></p>
            <?php endif; ?>
            <div class="article-body"><?php echo $post['content']; ?></div>
        </div>
        <?php if (!empty($tags)): ?>
        <div class="article-tags">
            <?php foreach ($tags as $t): ?>
            <a href="<?php echo base_url('search.php?q=' . urlencode($t['name'])); ?>" class="tag"><?php echo e($t['name']); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</article>

<!-- Related posts -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <div class="container">
        <h2 class="section-heading">Related Articles</h2>
        <div class="posts-grid three-col">
            <?php foreach ($related as $p): ?>
            <article class="post-card">
                <a href="<?php echo base_url('post.php?slug=' . e($p['slug'])); ?>" class="post-card-img">
                    <?php if (!empty($p['image'])): ?>
                    <img src="<?php echo base_url($p['image']); ?>" alt="<?php echo e($p['title']); ?>" loading="lazy">
                    <?php else: ?>
                    <div class="img-placeholder"></div>
                    <?php endif; ?>
                </a>
                <div class="post-card-body">
                    <h3><a href="<?php echo base_url('post.php?slug=' . e($p['slug'])); ?>"><?php echo e($p['title']); ?></a></h3>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Comments -->
<section class="comments-section">
    <div class="container">
        <h2 class="section-heading">Comments (<?php echo count($comments); ?>)</h2>
        <div class="comments-list">
            <?php foreach ($comments as $c): ?>
            <div class="comment">
                <strong><?php echo e($c['name']); ?></strong>
                <time><?php echo format_datetime($c['created_at']); ?></time>
                <p><?php echo nl2br(e($c['message'])); ?></p>
            </div>
            <?php endforeach; ?>
            <?php if (empty($comments)): ?>
            <p class="no-comments">No comments yet. Be the first to comment.</p>
            <?php endif; ?>
        </div>
        <!-- Social share -->
        <div class="share-buttons" aria-label="Share this article">
            <span class="share-label">Share:</span>
            <a href="https://twitter.com/intent/tweet?url=<?php echo rawurlencode($share_url); ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-twitter" aria-label="Share on Twitter">Twitter</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($share_url); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-facebook" aria-label="Share on Facebook">Facebook</a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo rawurlencode($share_url); ?>&title=<?php echo $share_title; ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-linkedin" aria-label="Share on LinkedIn">LinkedIn</a>
            <a href="https://wa.me/?text=<?php echo $share_title; ?>%20<?php echo rawurlencode($share_url); ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-whatsapp" aria-label="Share on WhatsApp">WhatsApp</a>
        </div>
        <p id="commentMessage" class="comment-form-msg" role="status" aria-live="polite"></p>
        <form class="comment-form" id="commentForm" action="<?php echo base_url('ajax/comment.php'); ?>" method="post">
            <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="comment_name">Name *</label>
                    <input type="text" id="comment_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="comment_email">Email *</label>
                    <input type="email" id="comment_email" name="email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="comment_message">Comment *</label>
                <textarea id="comment_message" name="message" rows="4" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Increment view count (once per page load)
    var postId = <?php echo (int)$post['id']; ?>;
    if (postId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo base_url("ajax/increment-view.php"); ?>?post_id=' + postId);
        xhr.send();
    }
    var form = document.getElementById('commentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.onload = function() {
                var res = JSON.parse(xhr.responseText || '{}');
                var msg = document.getElementById('commentMessage');
                if (msg) {
                    msg.textContent = res.message || (res.success ? 'Thank you. Your comment will appear after approval.' : 'Sorry, something went wrong.');
                    msg.className = 'comment-form-msg ' + (res.success ? 'success' : 'error');
                } else {
                    alert(res.message || (res.success ? 'Comment submitted. It will appear after approval.' : 'Error submitting comment.'));
                }
                if (res.success) form.reset();
            };
            xhr.send(fd);
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
