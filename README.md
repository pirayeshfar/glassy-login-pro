# Glassy Login Pro + Hide Login URL  
### Professional Glassy Custom Login Page for WordPress

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-21759B?logo=wordpress&logoColor=white&style=flat-square)
![Version](https://img.shields.io/badge/Version-2.4-brightgreen?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-%3E=7.4-777BB4?logo=php&logoColor=white&style=flat-square)
![License](https://img.shields.io/badge/License-GPLv2-blue?style=flat-square)

A modern, secure and fully customizable login page replacement with frosted-glass effect, background video/image support, complete hiding of default WordPress login URLs and advanced login attempt logging.

## ✨ Features

- Completely hides `wp-login.php` and blocks `/wp-admin` for non-logged-in users (403 Forbidden)
- Custom login slug → e.g. `yoursite.com/your-secret-slug`
- Beautiful frosted-glass login form with backdrop blur
- Full-screen background image **or** looping MP4 video
- Custom logo above the login form
- Removes all unnecessary links and elements:
  - Language switcher
  - “Lost your password?”
  - Back to blog / Privacy policy
- Logs every successful & failed login (username, IP, user-agent, timestamp) in database table `wp_glassy_login_logs`
- Clean settings page with WordPress Media Uploader
- No external dependencies

## 🛠 Installation

1. Download `glassy-login-pro.php`
2. Upload to `/wp-content/plugins/` or install via **Plugins → Add New → Upload Plugin**
3. Activate the plugin
4. Go to **Settings → Glassy Login Pro** and configure:
   - Custom login slug
   - Logo
   - Background image or video
5. Click **Save Changes** → then **Update Rewrite Rules**

> Default WordPress login URLs are blocked immediately after activation.

## ⚙️ Settings

| Option                  | Description                              |
|-------------------------|------------------------------------------|
| Login URL               | `yoursite.com/your-custom-slug`          |
| Logo                    | Upload from Media Library                |
| Background Image        | Full-screen static image                 |
| Background Video (MP4)  | Autoplay, muted, looped                  |

## 🔒 Security & Logging

- Direct access to `wp-login.php` → 403 error
- All login attempts stored in `wp_glassy_login_logs`
- Compatible with caching plugins (flush cache after slug change)

## 📋 Requirements

- WordPress 5.0+
- PHP 7.4+

## 📄 License

GPLv2 or later (same as WordPress)

---

**Developed by Amir Saman Pirayeshfar**  
Website: https://home-visa.ir  
Support & customizations available

❤️ If you like this plugin, please give it a star!
