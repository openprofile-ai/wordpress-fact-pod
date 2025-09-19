<?php
defined('ABSPATH') || exit;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ClientRepository;

$clientRepository = new ClientRepository();
$clients = $clientRepository->getClients();
?>

<h2>OAuth Clients</h2>
<table class="widefat">
  <thead>
    <tr>
      <th>Client ID</th>
      <th>Name</th>
      <th>Redirect URI</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($clients): ?>
      <?php foreach ($clients as $client): ?>
        <tr>
          <td><?php echo esc_html($client->getIdentifier()); ?></td>
          <td><?php echo esc_html($client->getName()); ?></td>
          <td><?php echo esc_html($client->getRedirectUri()); ?></td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr>
        <td colspan="3">No clients registered</td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>