<?php
defined('ABSPATH') || exit;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ScopeRepository;

$scopeRepository = new ScopeRepository();
$scopes = $scopeRepository->getAllScopes();
?>

<h2>OAuth Scopes</h2>
<div class="notice-info notice">
  <p>Active scopes will be available during OAuth authorization. Inactive scopes won't be offered to clients.</p>
</div>
<?php settings_errors('wpfp_messages'); ?>
<table class="widefat">
  <thead>
    <tr>
      <th>Scope</th>
      <th>Description</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($scopes): ?>
      <?php foreach ($scopes as $scope): ?>
        <tr>
          <td><?php echo esc_html($scope->getIdentifier()); ?></td>
          <td><?php echo esc_html($scope->getDescription()); ?></td>
          <td>
            <?php if ($scope->isActive()): ?>
              <span class="dashicons dashicons-yes" style="color: green;"></span> Active
            <?php else: ?>
              <span class="dashicons dashicons-no" style="color: red;"></span> Inactive
            <?php endif; ?>
          </td>
          <td>
            <form method="post" style="display: inline;">
              <?php wp_nonce_field('wpfp_update_scope'); ?>
              <input type="hidden" name="scope" value="<?php echo esc_attr($scope->getIdentifier()); ?>">
              <input type="hidden" name="is_active" value="<?php echo $scope->isActive() ? '0' : '1'; ?>">
              <button type="submit" name="wpfp_scope_update" class="button">
                <?php echo $scope->isActive() ? 'Deactivate' : 'Activate'; ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="4">No scopes defined</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>