# Changing Superadmin Credentials

This guide explains how to change the default superadmin credentials after setting up the project.

## Default Credentials

The default superadmin account created during database seeding is:
- **Email**: `admin@example.com`
- **Password**: `password`

**⚠️ IMPORTANT**: You should change these credentials immediately after first login in a production environment.

## Methods to Change Superadmin Credentials

### Method 1: Update via Database Seeder (Before First Run)

If you haven't run the migrations yet, you can update the seeder before running it:

1. Open `database/seeders/DatabaseSeeder.php`
2. Modify the superadmin user details:
   ```php
   User::factory()->create([
       'name' => 'Your Admin Name',
       'email' => 'your-email@your-domain.com',
       'password' => bcrypt('your-secure-password'),
       'role' => 'superadmin',
       'status' => 'active',
       'email_verified_at' => now(),
   ]);
   ```
3. Run migrations and seeding:
   ```bash
   php artisan migrate:fresh --seed
   ```

### Method 2: Update via Tinker (After Database is Seeded)

If the database is already seeded, you can update the credentials using Laravel Tinker:

1. Open Laravel Tinker:
   ```bash
   php artisan tinker
   ```

2. Find and update the superadmin user:
   ```php
   $admin = User::where('email', 'admin@example.com')->first();
   $admin->email = 'new-email@your-domain.com';
   $admin->password = bcrypt('new-secure-password');
   $admin->name = 'New Admin Name'; // Optional
   $admin->save();
   ```

3. Exit Tinker:
   ```php
   exit
   ```

### Method 3: Update via Profile Interface (Recommended for Production)

After logging in with the default credentials:

1. Log in with `admin@example.com` / `password`
2. Navigate to **Profile** from the user dropdown menu
3. Update your name, email, and password
4. Save changes

This is the recommended method as it uses the application's built-in validation and security features.

### Method 4: Direct Database Update (Advanced)

⚠️ **Use with caution**: Direct database manipulation should be avoided unless absolutely necessary.

1. Connect to your database
2. Update the user record:
   ```sql
   UPDATE users 
   SET email = 'new-email@your-domain.com', 
       password = '$2y$12$YOUR_BCRYPT_HASHED_PASSWORD',
       name = 'New Admin Name'
   WHERE role = 'superadmin';
   ```

Note: You need to generate a bcrypt hash for the password. You can use:
```bash
php artisan tinker
bcrypt('your-new-password')
```

## Security Best Practices

1. **Change immediately**: Always change default credentials before deploying to production
2. **Use strong passwords**: Use a password manager to generate and store complex passwords
3. **Enable 2FA**: Consider implementing two-factor authentication for superadmin accounts
4. **Regular audits**: Periodically review and rotate admin credentials
5. **Limit superadmin accounts**: Only create superadmin accounts when absolutely necessary

## Troubleshooting

### Forgot Password

If you forget your new password, you can:

1. Use the "Forgot Password" link on the login page (requires email configuration)
2. Reset via Tinker (Method 2 above)
3. Reseed the database (⚠️ This will delete all data):
   ```bash
   php artisan migrate:fresh --seed
   ```

### Email Not Working

If email verification or password reset emails aren't working:

1. Check your `.env` file has correct SMTP settings
2. Verify `MAIL_MAILER=smtp` is set
3. Test email configuration:
   ```bash
   php artisan tinker
   Mail::raw('Test email', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```

## Additional Resources

- [Laravel Authentication Documentation](https://laravel.com/docs/authentication)
- [Laravel Password Hashing](https://laravel.com/docs/hashing)
- [Laravel Tinker Documentation](https://laravel.com/docs/artisan#tinker)
