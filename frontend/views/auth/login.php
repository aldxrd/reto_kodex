<?php
/**
 * @var string $email @var array<string,string> $errors
 * @var string|null $message @var string|null $notice @var array $config
 */
?>
<div class="auth-column">
    <div class="auth-brand">
        <span class="brand-logo brand-logo-lg">K</span>
        <span>
            <span class="brand-name"><?= e($config['app_name']) ?></span>
            <span class="brand-sub"><?= e($config['company']) ?></span>
        </span>
    </div>

    <div class="card-k auth-card">
        <h1 class="h5 mb-1">Inicia sesión</h1>
        <p class="text-muted-k mb-4">Usa tu correo corporativo y contraseña.</p>

        <?php if ($notice): ?>
            <div class="notice notice-api mb-3" role="status"><i class="bi bi-info-circle" aria-hidden="true"></i><span><?= e($notice) ?></span></div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="notice notice-error mb-3" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span><?= e($message) ?></span></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/login')) ?>" novalidate id="login-form">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label">Correo</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="username" required autofocus
                       class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                       <?= isset($errors['email']) ? 'aria-describedby="email-error"' : '' ?>>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback" id="email-error"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" autocomplete="current-password" required
                           class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>"
                           <?= isset($errors['password']) ? 'aria-describedby="password-error"' : '' ?>>
                    <button class="btn btn-outline-k" type="button" data-toggle-password="password" aria-label="Mostrar contraseña">Mostrar</button>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback" id="password-error"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100" data-loading-text="Ingresando…">Iniciar sesión</button>
        </form>

        <p class="text-faint-k small text-center mt-3 mb-0">¿No puedes acceder? Contacta al administrador del sistema.</p>
    </div>

    <p class="text-faint-k small">Acceso restringido a personal autorizado.</p>
</div>
