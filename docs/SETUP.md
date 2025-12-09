# Setup Instructions

## Quick Start

### Step 1: Import Database

Open phpMyAdmin (http://localhost/phpmyadmin) and:
1. Click "New" to create a database
2. Or click "Import" tab
3. Choose the `database.sql` file
4. Click "Go"

Alternatively, use MySQL command line:
```bash
mysql -u root -p < database.sql
```

### Step 2: Access the Application

The server is already running! Just open your browser:

**URL:** http://localhost:52273

### Step 3: Login

Use one of these demo accounts:

**Admin:**
- Username: `admin`
- Password: `admin123`

**User:**
- Username: `johnDoe`
- Password: `user123`

## File Structure Created

```
newspostingsystem/
│
├── 📁 config/
│   └── database.php              # Database connection settings
│
├── 📁 includes/
│   ├── session.php               # Login/logout session handling
│   └── functions.php             # All core PHP functions
│
├── 📁 assets/
│   ├── 📁 css/
│   │   └── style.css             # Modern gradient design
│   └── 📁 uploads/                # Auto-created for images
│
├── 📁 admin/
│   ├── dashboard.php             # Admin homepage
│   └── all-posts.php             # View/manage all posts
│
├── 📁 user/
│   ├── dashboard.php             # User homepage & create post
│   └── my-posts.php              # User's post management
│
├── index.php                      # Login/registration page
├── logout.php                     # Logout handler
├── database.sql                   # Database schema & sample data
└── README.md                      # Full documentation
```

## How to Use

### As a User:

1. **Register** (if you don't have an account)
   - Click "Register here" on login page
   - Fill in username, email, password
   - Submit and login

2. **Create a Post**
   - After login, you'll see the create post form
   - Add title and content
   - Upload an image (optional)
   - Submit - it will go to "Pending" status

3. **View Newsfeed**
   - See all approved posts from everyone
   - Scroll through the latest news

4. **Manage Your Posts**
   - Click "My Posts" in navigation
   - See all your posts and their status
   - Delete posts you no longer want

### As an Admin:

1. **Login** with admin credentials

2. **Dashboard View**
   - See statistics: total users, posts, pending, approved
   - Review pending posts
   - Approve or reject posts
   - Delete any post

3. **Approve Posts**
   - Pending posts show at top of dashboard
   - Click "✓ Approve" to publish
   - Click "✗ Reject" to deny
   - Click "🗑 Delete" to remove

4. **Manage All Posts**
   - Click "All Posts" in navigation
   - Filter by: All, Pending, Approved, Rejected
   - Delete any post

## Features Implemented

✅ User registration and login
✅ Admin and user roles
✅ Create posts with text and images
✅ Image upload with preview
✅ Admin approval system
✅ Newsfeed of approved posts
✅ User can delete own posts
✅ Admin can delete any post
✅ Post status tracking (pending/approved/rejected)
✅ Beautiful modern UI with gradients
✅ Responsive design
✅ Secure password hashing
✅ SQL injection protection
✅ XSS protection

## Troubleshooting

**Can't upload images?**
- The `assets/uploads/` folder will be created automatically
- Make sure PHP has write permissions

**Database connection error?**
- Check `config/database.php` settings
- Make sure MySQL is running
- Verify database name and credentials

**Login not working?**
- Make sure you imported `database.sql`
- Try the demo accounts first
- Check if sessions are enabled in PHP

Enjoy your News Posting System! 🎉
