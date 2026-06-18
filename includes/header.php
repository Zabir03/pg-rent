<?php
/** Layout header. Set $page_title before including. Requires login. */
require_once __DIR__ . '/functions.php';
require_login();
$u = current_user();
$role = $u['role'];
$page_title = $page_title ?? 'Dashboard';

// Nav items per role
$nav = $role === 'super_admin'
    ? [
        ['/dashboard/index.php', 'Dashboard', '▦'],
        ['/dashboard/owners.php', 'PG Owners', '👥'],
        ['/dashboard/subscriptions.php', 'Subscriptions', '💳'],
        ['/dashboard/tickets.php', 'Support', '🎫'],
        ['/dashboard/logs.php', 'Activity Logs', '🧾'],
      ]
    : [
        ['/dashboard/index.php', 'Dashboard', '▦'],
        ['/students/index.php', 'Students', '🎓'],
        ['/rooms/index.php', 'Rooms & Beds', '🛏'],
        ['/rent/index.php', 'Rent', '💰'],
        ['/expenses/index.php', 'Expenses', '🧮'],
        ['/dashboard/reports.php', 'Reports', '📊'],
        ['/support/index.php', 'Support', '🎫'],
        ['/subscription/index.php', 'Subscription', '💳'],
        ['/settings/index.php', 'Settings', '⚙'],
      ];
$current = $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — PG Rent Manager</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <div class="brand">PG<span>Rent</span></div>
    <nav>
      <?php foreach ($nav as [$href, $label, $icon]): ?>
        <a href="<?= e($href) ?>" class="<?= str_ends_with($current, basename($href)) ? 'active' : '' ?>">
          <span class="ic"><?= $icon ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
      <h2><?= e($page_title) ?></h2>
      <div class="topbar-right">
        <?php $nUnread = unread_count($u['id']); ?>
        <a class="bell" href="/notifications/index.php" title="Notifications">
          🔔<?php if ($nUnread > 0): ?><span class="bell-dot"><?= $nUnread > 9 ? '9+' : $nUnread ?></span><?php endif; ?>
        </a>
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">🌓</button>
        <span class="user-chip"><?= e($u['name']) ?> · <?= e($role) ?></span>
        <a class="btn btn-ghost" href="/auth/logout.php">Logout</a>
      </div>
    </header>
    <main class="content">
      <?= flash_render() ?>
