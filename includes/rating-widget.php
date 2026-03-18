<?php
// includes/rating-widget.php
// Clean, single implementation of the rating widget (UTF-8, no BOM)
// Expects: $product_id defined before include

if (!isset($product_id)) {
    return;
}

// Load dependencies (use absolute/robust paths)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Rating.php';

$ratingModel = new Rating();
$avg = $ratingModel->getAverageRating($product_id) ?: ['avg_rating' => 0, 'total_ratings' => 0];

$distRows = $ratingModel->getRatingDistribution($product_id);
$distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
if (is_array($distRows)) {
    foreach ($distRows as $r) {
        $k = isset($r['rating']) ? (int)$r['rating'] : (int)($r[0] ?? 0);
        $v = isset($r['count']) ? (int)$r['count'] : (int)($r[1] ?? 0);
        if ($k >= 1 && $k <= 5) {
            $distribution[$k] = $v;
        }
    }
}

$ratings = $ratingModel->getRatingsByProduct($product_id, 5) ?: [];
$total = max(0, (int)$ratingModel->countRatingsByProduct($product_id));
// Không cần lấy userRating nữa vì cho phép đánh giá nhiều lần

?>

<div class="rating-widget" style="background:#f8f9fa;padding:18px;border-radius:10px;margin:18px 0;color:#333;">
  <div style="display:flex;gap:18px;align-items:flex-start;">
    <div style="width:160px;text-align:center;">
      <div style="font-size:36px;font-weight:700;color:#ffc107;">
        <?php echo number_format($avg['avg_rating'] ?? 0, 1); ?>
      </div>
      <div style="color:#666;margin-top:6px;">(<?php echo (int)($avg['total_ratings'] ?? 0); ?> đánh giá)</div>
    </div>

    <div style="flex:1;">
      <?php foreach ([5,4,3,2,1] as $s) {
          $count = isset($distribution[$s]) ? (int)$distribution[$s] : 0;
          $pct = $total > 0 ? round($count / $total * 100) : 0;
      ?>
        <div style="display:flex;align-items:center;gap:12px;margin:6px 0;">
          <div style="width:44px;text-align:right;color:#333;"><?php echo $s; ?> <span style="color:#ffc107;">★</span></div>
          <div style="flex:1;height:14px;background:#e9ecef;border-radius:8px;overflow:hidden;">
            <div style="height:100%;background:#ffc107;width:<?php echo $pct; ?>%"></div>
          </div>
          <div style="width:36px;text-align:left;color:#333;"><?php echo $count; ?></div>
        </div>
      <?php } ?>
    </div>
  </div>

  <div style="margin-top:14px;background:#fff;padding:12px;border-radius:8px;">
    <?php if (isCustomerLoggedIn()) { ?>
      <form id="ratingForm" method="post" action="<?php echo SITE_URL; ?>/api/rating-handler.php">
        <input type="hidden" name="action" value="submit_rating">
        <input type="hidden" name="product_id" value="<?php echo (int)$product_id; ?>">
        <div id="starSelect" style="display:flex;gap:6px;margin-bottom:8px;">
          <?php for ($i = 5; $i >= 1; $i--) { ?>
            <button type="button" class="star-btn" data-rating="<?php echo $i; ?>" style="border:none;background:none;cursor:pointer;color:#ccc;font-size:20px;">★</button>
          <?php } ?>
        </div>
        <input type="hidden" id="rating-score" name="rating" value="0" required>
        <div style="margin-bottom:10px;"><textarea name="content" id="rating-comment" placeholder="Viết nhận xét..." style="width:100%;height:80px;padding:8px;border:1px solid #ddd;border-radius:6px;"></textarea></div>
        <div style="display:flex;gap:8px;"><button type="submit" style="background:var(--primary-color);color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;">Gửi</button></div>
      </form>
    <?php } else { ?>
      <div style="color:#856404;background:#fff3cd;padding:8px;border-radius:6px;">Vui lòng <a href="/diep-anh-shop/customer/login.php">đăng nhập</a> để gửi đánh giá.</div>
    <?php } ?>
  </div>

  <?php if (!empty($ratings)) { ?>
    <div style="margin-top:12px;">
      <?php foreach ($ratings as $review) { ?>
        <div style="border-top:1px solid #eee;padding:10px 0;">
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong><?php echo htmlspecialchars($review['full_name'] ?? ($review['username'] ?? 'Khách'), ENT_QUOTES); ?></strong>
              <div style="color:#ffc107;font-size:13px;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <span style="color: <?php echo $i <= (int)$review['rating'] ? '#ffc107' : '#ddd'; ?>;">★</span>
                <?php endfor; ?>
              </div>
            </div>
            <div style="color:#999;font-size:12px;"><?php echo isset($review['created_at']) ? formatDate($review['created_at'], 'd/m/Y H:i') : date('d/m/Y'); ?></div>
          </div>
          <?php if (!empty($review['content'])): ?>
          <div style="margin-top:8px;color:#555;line-height:1.6;"><?php echo nl2br(htmlspecialchars($review['content'], ENT_QUOTES)); ?></div>
          <?php endif; ?>
        </div>
      <?php } ?>
    </div>
  <?php } else { ?>
    <div style="text-align:center;color:#999;padding:18px;">Chưa có đánh giá nào. Hãy là người đầu tiên!</div>
  <?php } ?>

</div>

<script>
// Star UI
;(function(){
  var stars = document.querySelectorAll('#starSelect .star-btn');
  var score = document.getElementById('rating-score');
  function setStars(v){
    var warningColor = getComputedStyle(document.documentElement).getPropertyValue('--warning-color') || '#D4A574';
    stars.forEach(function(b,i){ var r = 5 - i; b.style.color = (r <= v) ? warningColor : '#ccc'; });
  }
  stars.forEach(function(b){ b.addEventListener('click', function(){ var v = parseInt(this.getAttribute('data-rating'),10); score.value = v; setStars(v); }); });
  setStars(parseInt(score.value||0,10));
  
  // Form submission
  var form = document.getElementById('ratingForm');
  var apiUrl = '<?php echo SITE_URL; ?>/api/rating-handler.php';
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      if (!score.value || score.value < 1 || score.value > 5) {
        alert('Vui lòng chọn số sao đánh giá');
        return;
      }
      var formData = new FormData(form);
      fetch(apiUrl, {
        method: 'POST',
        body: formData
      })
      .then(r => {
        if (!r.ok) {
          throw new Error('HTTP error! status: ' + r.status);
        }
        return r.json();
      })
      .then(d => {
        if (d.success) {
          alert(d.message || 'Đánh giá thành công');
          location.reload();
        } else {
          alert('Lỗi: ' + (d.message || 'Không thể gửi đánh giá'));
        }
      })
      .catch(e => {
        console.error('Error:', e);
        alert('Lỗi: ' + (e.message || 'Không thể kết nối đến server'));
      });
    });
  }
})();
</script>


