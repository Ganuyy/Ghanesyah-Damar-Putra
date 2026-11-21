<?php
/**
 * admin/users.php - Kelola User
 * Hanya admin.
 *
 * Bisa: mencari user, ganti level belajar, jadikan/lepas admin,
 * aktif/nonaktifkan akun, dan hapus akun.
 * Akun sendiri tidak boleh dinonaktifkan, diubah role-nya, atau dihapus.
 */
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/layout.php';

require_admin();

$me = current_user();

// ==========================================================
//  AKSI (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $action = p('action');
    $uid    = (int) p('id');

    $isSelf = ($uid === (int) $me['id']);

    if ($uid <= 0) {
        flash_set('error', 'User tidak valid.');
        redirect('admin/users.php');
    }
    if ($isSelf && in_array($action, ['toggle_active', 'set_role', 'delete'], true)) {
        flash_set('error', 'Kamu tidak bisa mengubah/menghapus akunmu sendiri.');
        redirect('admin/users.php');
    }

    switch ($action) {
        case 'set_level':
            $lv = p('level');
            if (!in_array($lv, level_options(), true)) {
                flash_set('error', 'Level tidak valid.');
                break;
            }
            db()->prepare('UPDATE users SET level = ? WHERE id = ?')->execute([$lv, $uid]);
            log_activity('ubah_level_user', "Set level user #$uid jadi $lv");
            flash_set('success', 'Level user berhasil diperbarui.');
            break;

        case 'set_role':
            $role = p('role');
            if (!in_array($role, ['user', 'admin'], true)) {
                flash_set('error', 'Role tidak valid.');
                break;
            }
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
            log_activity('ubah_role_user', "Set role user #$uid jadi $role");
            flash_set('success', 'Hak akses user berhasil diperbarui.');
            break;

        case 'toggle_active':
            db()->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$uid]);
            log_activity('toggle_user', "Ubah status aktif user #$uid");
            flash_set('success', 'Status akun berhasil diubah.');
            break;

        case 'reset_progress':
            db()->prepare('DELETE FROM user_chord_progress WHERE user_id = ?')->execute([$uid]);
            db()->prepare('DELETE FROM user_song_progress WHERE user_id = ?')->execute([$uid]);
            log_activity('reset_progress', "Reset progress user #$uid");
            flash_set('success', 'Progress user berhasil direset.');
            break;

        case 'delete':
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            log_activity('hapus_user', "Menghapus user #$uid");
            flash_set('success', 'Akun berhasil dihapus.');
            break;

        default:
            flash_set('error', 'Aksi tidak dikenal.');
    }

    redirect('admin/users.php');
}

// ==========================================================
//  DAFTAR USER
// ==========================================================
$search = q('q');
$role   = q('role');
if (!in_array($role, ['user', 'admin'], true)) {
    $role = '';
}

$sql = 'SELECT u.*,
               (SELECT COUNT(*) FROM user_chord_progress p
                 WHERE p.user_id = u.id AND p.status = \'kuasai\') AS dikuasai
          FROM users u WHERE 1 = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (u.username LIKE ? OR u.email LIKE ? OR u.fullname LIKE ?)';
    $params = array_fill(0, 3, '%' . $search . '%');
}
if ($role !== '') {
    $sql .= ' AND u.role = ?';
    $params[] = $role;
}
$sql .= ' ORDER BY u.role, u.username';

$st = db()->prepare($sql);
$st->execute($params);
$users = $st->fetchAll();

$totalUser  = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalAdmin = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

layout_head('Kelola User', 'admin', ['wide' => true]);
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="<?= e(url('admin/index.php')) ?>" class="text-warning text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active text-dim">Kelola User</li>
      </ol>
    </nav>
    <h1 class="h3 mb-0"><i class="bi bi-people me-2 text-warning"></i>Kelola User</h1>
  </div>
  <form class="d-flex gap-2" method="get" action="<?= e(url('admin/users.php')) ?>">
    <select name="role" class="form-select form-select-sm" style="width:auto">
      <option value="">Semua role</option>
      <option value="user"  <?= $role === 'user'  ? 'selected' : '' ?>>User (<?= $totalUser ?>)</option>
      <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin (<?= $totalAdmin ?>)</option>
    </select>
    <input type="search" name="q" class="form-control form-control-sm" placeholder="Cari nama / email"
           value="<?= e($search) ?>" style="min-width:170px">
    <button class="btn btn-sm btn-accent-outline" type="submit">Cari</button>
  </form>
</div>

<?php if (!$users): ?>
  <div class="gl-card p-5 text-center">
    <i class="bi bi-people fs-1 text-dim"></i>
    <p class="text-dim mt-2 mb-0">Tidak ada user yang cocok.</p>
  </div>
<?php else: ?>
  <div class="gl-card p-3">
    <div class="table-responsive">
      <table class="table table-dark-gl align-middle mb-0">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Level</th>
            <th class="text-center">Dikuasai</th>
            <th>Hak Akses</th>
            <th class="text-center">Status</th>
            <th class="text-end" style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u):
              $isSelf = ((int) $u['id'] === (int) $me['id']);
          ?>
            <tr>
              <td>
                <div class="fw-bold"><?= e($u['username']) ?>
                  <?php if ($isSelf): ?><span class="badge bg-warning text-dark ms-1">kamu</span><?php endif; ?>
                </div>
                <?php if ($u['fullname']): ?>
                  <div class="small text-dim"><?= e($u['fullname']) ?></div>
                <?php endif; ?>
              </td>
              <td class="small text-dim"><?= e($u['email']) ?></td>

              <!-- Ganti level -->
              <td>
                <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-flex">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="set_level">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <select name="level" class="form-select form-select-sm" onchange="this.form.submit()"
                          style="width:auto">
                    <option value="beginner"     <?= $u['level'] === 'beginner'     ? 'selected' : '' ?>>Beginner</option>
                    <option value="intermediate" <?= $u['level'] === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                    <option value="pro"          <?= $u['level'] === 'pro'          ? 'selected' : '' ?>>Pro</option>
                    <option value=""             <?= !$u['level'] ? 'selected' : '' ?>>&mdash; belum &mdash;</option>
                  </select>
                </form>
              </td>

              <td class="text-center fw-bold"><?= (int) $u['dikuasai'] ?></td>

              <!-- Ganti role -->
              <td>
                <?php if ($isSelf): ?>
                  <span class="badge bg-<?= $u['role'] === 'admin' ? 'warning text-dark' : 'secondary' ?>">
                    <?= e($u['role']) ?>
                  </span>
                <?php else: ?>
                  <form method="post" action="<?= e(url('admin/users.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="set_role">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                    <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none"
                            title="Klik untuk mengubah">
                      <span class="badge bg-<?= $u['role'] === 'admin' ? 'warning text-dark' : 'secondary' ?>">
                        <?= e($u['role']) ?>
                        <i class="bi bi-arrow-left-right ms-1"></i>
                      </span>
                    </button>
                  </form>
                <?php endif; ?>
              </td>

              <!-- Status aktif -->
              <td class="text-center">
                <?php if ((int) $u['is_active'] === 1): ?>
                  <span class="badge bg-success">Aktif</span>
                <?php else: ?>
                  <span class="badge bg-danger">Nonaktif</span>
                <?php endif; ?>
              </td>

              <!-- Aksi -->
              <td class="text-end">
                <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-inline"
                      onsubmit="return confirm('Reset progress belajar user ini?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="reset_progress">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-secondary" title="Reset progress">
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </button>
                </form>

                <?php if (!$isSelf): ?>
                  <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-warning"
                            title="<?= (int) $u['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>">
                      <i class="bi bi-<?= (int) $u['is_active'] === 1 ? 'pause' : 'play' ?>"></i>
                    </button>
                  </form>

                  <form method="post" action="<?= e(url('admin/users.php')) ?>" class="d-inline"
                        onsubmit="return confirm('Hapus akun <?= e($u['username']) ?>? Semua progress ikut terhapus.');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p class="text-dim small mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Klik badge <strong>role</strong> untuk menjadikan seorang user sebagai admin atau sebaliknya.
    Menonaktifkan akun membuat user tersebut tidak bisa login.
  </p>
<?php endif; ?>

<?php layout_foot(); ?>
