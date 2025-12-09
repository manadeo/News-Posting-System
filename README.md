# News-Posting-System
A full-featured news posting and content management platform built with PHP, MySQL, and modern web technologies. This system provides role-based access control with separate interfaces for administrators and regular users, complete with post moderation, scheduling, and community engagement features.

**✨ Features**
**User Management**
- User registration and authentication system
- Role-based access control (Admin/User)
- Profile management with customizable user information
- Secure password hashing and session management

**Content Management**
- Create, edit, and delete posts with rich content and images
- Post scheduling for future publication
- Admin announcements with priority display
- Post approval workflow (pending/approved/rejected)
- Image upload support with automatic file management

**Community Engagement**
- Threaded comment system with nested replies
- Edit and delete your own comments
- Real-time comment interactions via AJAX
- Content moderation with harmful content filtering

**Admin Dashboard**
- Comprehensive statistics overview
- User management (view, edit, delete users)
- Post moderation queue
- Announcement creation and scheduling
- Full CRUD operations on all content

**User Dashboard**
- Personal news feed with approved posts
- Create and manage your own posts
- View post history and status
- Comment on posts and engage with community

**🛠️ Technology Stack**
- Backend: PHP 7.4+
- Database: MySQL/MariaDB with PDO
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Server: Apache (XAMPP compatible)
- Architecture: MVC-inspired structure with separation of concerns

**📁 Project Structure**
np/
├── admin/           # Admin panel and management interfaces
├── user/            # User dashboard and interfaces
├── assets/          # CSS, JavaScript, and uploaded images
├── config/          # Database configuration
├── includes/        # Core functions and session management
├── docs/            # Documentation
└── index.php        # Login/registration entry point

**🚀 Features in Detail**
**Post Scheduling:** Schedule announcements and posts for future publication with automatic visibility control
**Content Filtering:** Built-in harmful content detection to maintain community standards

**Responsive Design:** Modern, clean UI with smooth animations and user-friendly interactions

**Time Localization:** Philippine timezone support with "time ago" formatting

**Security:** Input sanitization, prepared statements, and password verification

**📋 Requirements**
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB
- Apache web server
- PDO PHP extension

**🎯 Use Cases**
Perfect for:
- Community news platforms
- Internal company announcements
- Blog platforms with moderation
- Content publishing workflows
- Educational news posting systems
