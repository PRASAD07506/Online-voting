# Hosting Deployment

## Upload Files

Upload the full project contents to your hosting account `htdocs` folder.

Important folders/files:

- `index.php`
- `style.css`
- `auth/`
- `admin/`
- `user/`
- `config/`
- `assets/`
- `uploads/faces/`
- `sql/hosting_setup.sql`

## Database

1. Open InfinityFree control panel.
2. Create a MySQL database.
3. Open phpMyAdmin for that database.
4. Import `sql/hosting_setup.sql`.

## Database Config

For live hosting, replace `config/dp.php` with the contents of `config/dp.hosting.php`.

If your InfinityFree DB host/user/password/database name are different, update them first.

## Test URLs

- `/`
- `/auth/register.php`
- `/auth/user_login.php`
- `/auth/admin_login.php`
- `/auth/admin_register.php`

## Admin Registration

Change the admin registration code in `config/admin_access.php` before going live.

## Face Uploads

Make sure `uploads/faces/` exists on the server. The app stores enrolled face images there.
