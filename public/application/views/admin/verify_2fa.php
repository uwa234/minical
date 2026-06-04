<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-factor authentication — Platform Admin</title>
    <?php echo platform_branding_favicon_link_html(); ?>
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
                <?php $pa_login_logo = platform_website_logo_url(); ?>
                <?php if ($pa_login_logo !== '') { ?>
                    <img class="pa-login-logo-img" src="<?php echo htmlspecialchars($pa_login_logo, ENT_QUOTES, 'UTF-8'); ?>" alt="Platform logo" />
                <?php } else { ?>
                    <div class="pa-login-logo" aria-hidden="true"></div>
                <?php } ?>
                <h1>Two-factor authentication</h1>
                <p>Enter the 6-digit code from your authenticator app</p>
            </div>
            <div class="pa-login-body">
                <?php if (!empty($errors)) {
                    foreach ($errors as $err) { ?>
                        <div class="pa-alert pa-alert-danger" style="margin-bottom: 20px;">
                            <?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php }
                } ?>
                <form method="post" action="<?php echo base_url('admin/verify_2fa'); ?>">
                    <div class="form-group">
                        <label for="totp_code">Authentication code</label>
                        <input type="text" class="form-control" id="totp_code" name="totp_code" required
                               inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                               placeholder="000000" autofocus>
                    </div>
                    <button type="submit" name="submit" value="1" class="btn btn-primary btn-block">Verify</button>
                </form>
            </div>
            <div class="pa-login-footer">
                <a href="<?php echo base_url('auth/logout'); ?>">Sign out</a>
            </div>
        </div>
    </div>
</body>
</html>
