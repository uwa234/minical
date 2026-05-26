<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platform Admin — Veurion</title>
    <link rel="stylesheet" href="<?php echo base_url('css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url() . auto_version('css/admin/platform-admin.css'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
</head>
<body class="pa-login">
    <div class="pa-login-wrap">
        <div class="pa-login-card">
            <div class="pa-login-header">
                <div class="pa-login-logo" aria-hidden="true"></div>
                <h1>Platform Admin</h1>
                <p>Sign in to manage tenants and platform settings</p>
            </div>
            <div class="pa-login-body">
                <?php if (!empty($errors)) {
                    foreach ($errors as $err) { ?>
                        <div class="pa-alert pa-alert-danger" style="margin-bottom: 20px;">
                            <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php }
                } ?>
                <form method="post" action="<?php echo base_url('admin/login'); ?>">
                    <div class="form-group">
                        <label for="login">Email address</label>
                        <input type="email" class="form-control" id="login" name="login" required
                               placeholder="you@company.com"
                               value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required
                               placeholder="Enter your password">
                    </div>
                    <button type="submit" name="submit" value="1" class="btn btn-primary btn-block">Sign in</button>
                </form>
            </div>
            <div class="pa-login-footer">
                <a href="<?php echo base_url(); ?>">Back to homepage</a>
                &nbsp;&middot;&nbsp;
                <a href="<?php echo base_url('auth/login'); ?>">Hotel login</a>
            </div>
        </div>
    </div>
</body>
</html>
