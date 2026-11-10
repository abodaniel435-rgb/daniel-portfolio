<?php $site = site_config(); ?>
<div class="portal">
  <?php include BASE_PATH . '/views/partials/admin_nav.php'; ?>
  <div class="portal-main">
    <div class="page-head">
      <div><h1>Catalogue &amp; stock</h1><p>Orders decrement stock inside a transaction; restocking is additive and audited.</p></div>
    </div>

    <div class="panel">
      <div class="panel-head"><h2>Add a product</h2></div>
      <form id="add-product" class="form-grid form-grid-inline" method="post" action="<?= e(url('/admin/products')) ?>">
        <?= csrf_field() ?>
        <div><label for="name">Name</label><input id="name" name="name" required></div>
        <div><label for="sku">SKU</label><input id="sku" name="sku" required placeholder="PKG-001"></div>
        <div><label for="price">Price (₦)</label><input id="price" name="price" type="number" min="0" step="1" required></div>
        <div><label for="stock">Stock</label><input id="stock" name="stock" type="number" min="0" required></div>
        <div class="span-2"><label for="description">Description</label><input id="description" name="description" maxlength="500"></div>
        <div class="form-submit-inline"><button class="btn btn-primary" type="submit">Add product</button></div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>SKU</th><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th>Restock</th></tr></thead>
        <tbody>
          <?php foreach ($products as $product): ?>
            <tr>
              <td class="mono"><?= e($product['sku']) ?></td>
              <td><strong><?= e($product['name']) ?></strong><br><span class="cell-muted"><?= e($product['description']) ?></span></td>
              <td><?= money_fmt((float)$product['price']) ?></td>
              <td><?= (int)$product['stock'] ?></td>
              <td><?= (int)$product['is_active'] === 1 ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-neutral">Hidden</span>' ?></td>
              <td>
                <form class="inline-form" method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/restock')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                  <input name="quantity" type="number" min="1" max="10000" value="10" style="width:88px" aria-label="Restock quantity">
                  <button class="btn btn-quiet btn-sm" type="submit">Add stock</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
