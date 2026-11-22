<nav class="navbar navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="../index.php">Peer Tutoring Hub</a>
    <div class="d-flex flex-wrap align-items-center gap-3">
      <?php if (isset($user) && $user): ?>
        <a class="link-light" href="profile.php">My profile</a>
        <?php if (in_array($user['role'], ['Student', 'Administrator'], true)): ?>
          <a class="link-light" href="requests.php">Requests</a>
        <?php endif; ?>
        <?php if ($user['role'] === 'Student'): ?>
          <a class="link-light" href="request-form.php">Request a session</a>
        <?php elseif ($user['role'] === 'Administrator'): ?>
          <a class="link-light" href="users.php">Manage users</a>
        <?php endif; ?>
        <form method="post" action="logout.php" class="m-0">
          <?= csrfField() ?>
          <button class="btn btn-outline-light btn-sm">Log out</button>
        </form>
      <?php else: ?>
        <a class="link-light" href="login.php">Log in</a>
        <a class="link-light" href="register.php">Create account</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
