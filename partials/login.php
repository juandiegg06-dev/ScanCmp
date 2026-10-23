<?php $titulo = 'Ingresar'; include __DIR__ . '/header.php'; ?>
<form method="post" class="card login">
  <h1>Ingresar</h1>
  <?php if (!empty($error)): ?><p class="error">Contraseña incorrecta</p><?php endif; ?>
  <input type="password" name="password" placeholder="Contraseña" autofocus required>
  <button>Entrar</button>
</form>
<?php include __DIR__ . '/footer.php'; ?>
