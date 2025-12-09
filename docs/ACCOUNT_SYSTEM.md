# Account System Overview

## How the Account System Works

### 🔐 **Admin Account (Fixed)**

- **Cannot be created through registration**
- **Pre-configured in the database**
- **Single admin account** manages the entire system

**Admin Credentials:**
- Username: `admin`
- Password: `admin123`
- Role: `admin` (fixed, cannot be changed)

**Admin Capabilities:**
- Approve or reject user posts
- Delete any post (including user posts)
- View all posts with filters
- Access admin dashboard with statistics

---

### 👤 **User Accounts (Self-Registration)**

- **Can be created by anyone** through the registration form
- **Automatically assigned "user" role**
- **Multiple users** can register and use the system

**User Capabilities:**
- Create posts with text and images
- View newsfeed of approved posts
- Manage their own posts
- Delete their own posts
- Cannot approve or manage other users' posts

---

## Account Creation Process

### For the Admin:
1. Admin account is created via:
   - Database import (`database.sql`)
   - OR running `create_users.php` once during setup
2. Admin credentials are fixed and cannot be changed through the UI
3. Only ONE admin account exists

### For Users:
1. Visit the registration page
2. Fill in:
   - Username
   - Email
   - Password
3. Submit registration
4. Account is created with "user" role
5. Login and start creating posts

---

## Post Workflow

```
USER creates post
    ↓
Post status: PENDING
    ↓
ADMIN reviews post
    ↓
ADMIN approves/rejects
    ↓
If APPROVED → Shows in newsfeed
If REJECTED → Hidden from newsfeed
    ↓
USER can delete own posts
ADMIN can delete any posts
```

---

## Security Features

✅ **Role-based Access Control**
- Admin pages require admin role
- User pages require login
- Automatic redirects based on role

✅ **Password Security**
- Passwords hashed with bcrypt
- Password verification on login
- Minimum 6 characters required

✅ **Session Management**
- Secure PHP sessions
- Automatic logout functionality
- Session validation on each page

---

## Initial Setup

1. **Import Database:**
   ```sql
   -- Run database.sql in phpMyAdmin or MySQL
   ```

2. **Create Admin Account:**
   ```
   Visit: http://localhost/newspostingsystem/create_users.php
   This creates the admin account with proper password hashing
   ```

3. **Users Can Register:**
   ```
   Users visit the registration page and create their own accounts
   ```

---

## Important Notes

⚠️ **Admin Account:**
- The admin account is **FIXED** and should not be changed
- Do not allow admin registration through the public form
- Admin is created only once during setup

✅ **User Accounts:**
- Users can freely register
- All registered users get "user" role automatically
- No manual approval needed for user registration

🔒 **Security:**
- Never hardcode passwords in the code
- Always use password hashing
- The `create_users.php` script should be deleted or protected after initial setup
