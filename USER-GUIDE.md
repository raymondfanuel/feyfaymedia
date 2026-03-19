# FeyFay Media – Complete User & Feature Guide

This guide explains **every function** of the FeyFay Media News Blog CMS: what each part does, how to use it, and where to find it.

---

## Table of contents

1. [What is FeyFay Media?](#1-what-is-feyfay-media)
2. [For visitors (public website)](#2-for-visitors-public-website)
3. [For staff and admins (logging in)](#3-for-staff-and-admins-logging-in)
4. [User roles (Staff vs Admin)](#4-user-roles-staff-vs-admin)
5. [Admin dashboard](#5-admin-dashboard)
6. [Posts – create, edit, schedule](#6-posts--create-edit-schedule)
7. [Categories](#7-categories)
8. [Comments moderation](#8-comments-moderation)
9. [Settings (Admin only)](#9-settings-admin-only)
10. [Staff management (Admin only)](#10-staff-management-admin-only)
11. [Contact form and newsletter](#11-contact-form-and-newsletter)
12. [Ads and monetization](#12-ads-and-monetization)
13. [SEO and sitemap](#13-seo-and-sitemap)
14. [Installation and configuration](#14-installation-and-configuration)
15. [File and folder structure](#15-file-and-folder-structure)
16. [Database tables](#16-database-tables)
17. [Security and best practices](#17-security-and-best-practices)
18. [Troubleshooting](#18-troubleshooting)

---

## 1. What is FeyFay Media?

FeyFay Media is a **news blog CMS** (Content Management System) built with **plain PHP and MySQL**. It has:

- A **public website** where readers see articles, search, comment, and subscribe.
- An **admin area** where staff and admins log in to write posts, manage categories, moderate comments, and (for admins) change site settings and manage users.

No framework: just PHP files, HTML, CSS, JavaScript, and a MySQL database.

---

## 2. For visitors (public website)

### 2.1 Homepage (`index.php`)

**URL:** `https://yoursite.com/` or `https://yoursite.com/index.php`

**What you see:**

- **Header** – Site logo (FeyFay logo by default), main menu (Home, categories, About, Contact), search box. If you added a header ad in Settings, it appears below the menu and **sticks at the top** when scrolling.
- **Breaking news ticker** – The latest few published articles as a scrolling ticker.
- **Featured hero** – One “featured” article (big image + title + summary). Only one post can be featured at a time; you set it in Add/Edit Post.
- **Latest news grid** – Recent published (and already “live” scheduled) posts in a grid.
- **Sidebar** – Trending (most viewed), recent posts, newsletter signup, and optional sidebar ad.
- **Category sections** – Blocks of latest posts per category (e.g. Technology, Sports).
- **Homepage ad** – If set in Settings, an ad block appears between Latest News and category sections.

Only **published** posts whose **publish time has passed** (or “Publish now”) appear. Drafts and future-scheduled posts are hidden.

---

### 2.2 Single post page (`post.php`)

**URL:** `https://yoursite.com/post.php?slug=article-title`

**What you see:**

- Category badge, optional “Sponsored” badge, title, author, date, view count.
- Featured image (if set).
- Optional in-article ad (from Settings).
- Summary and full article content (with rich text: headings, lists, links, images, blockquotes, embedded video).
- Tags (clickable for search).
- Related posts (same category).
- Comments (only approved ones show).
- Comment form (name, email, message). Comments are saved as “pending” until a staff/admin approves them in Admin → Comments.

**SEO:** The page uses the post’s meta title and description (or falls back to title/summary), plus Open Graph and Twitter Card tags for sharing.

---

### 2.3 Category page (`category.php`)

**URL:** `https://yoursite.com/category.php?slug=technology`

**What you see:**

- List of published posts in that category, **paginated** (12 per page by default).
- Same header/footer and sidebar as the rest of the site.

---

### 2.4 Search (`search.php`)

**URL:** `https://yoursite.com/search.php?q=keyword`

**What you see:**

- Paginated list of published posts where the **title**, **summary**, or **content** contains the search term.

---

### 2.5 About page (`about.php`)

**URL:** `https://yoursite.com/about.php`

Static “About” page with a short description and a link to the Contact page. You can edit the text in the file if needed (or later add a settings field).

---

### 2.6 Contact page (`contact.php`)

**URL:** `https://yoursite.com/contact.php`

**Two forms on the same page:**

1. **Contact form**  
   - Fields: Name, Email, Subject (optional), Message.  
   - On submit, an email is sent to the **Contact email** set in Admin → Settings (or a default).  
   - If you use **MailHog** for testing, set `MAIL_SMTP_HOST` and `MAIL_SMTP_PORT` in `includes/config.php` so emails go to MailHog (e.g. view at `http://localhost:8025`).

2. **Newsletter signup**  
   - Also on the **sidebar** on other pages: email only.  
   - Subscribers are stored in the `subscribers` table.  
   - You’ll see “Thank you for subscribing” or “You are already subscribed” after submit.

---

### 2.7 404 page (`404.php`)

When a visitor opens a non-existent URL (e.g. wrong slug or missing post), the site shows a custom “Page not found” page.

---

### 2.8 Favicon

The site uses **`assets/images/feyfaylogo.png`** as the favicon (browser tab and bookmarks) on both the public site and the admin area.

---

## 3. For staff and admins (logging in)

### 3.1 Login page

**URL:** `https://yoursite.com/admin/login.php`

- Enter **username** and **password**.
- Only **active** users can log in (Admin can deactivate users in Staff).
- After login you are redirected to the dashboard (or the page you had in `?redirect=...`).
- **Security:** Login form is protected with a CSRF token; session cookie is HttpOnly and SameSite.

### 3.2 Logout

**URL:** `https://yoursite.com/admin/logout.php`

- Destroys the session and redirects to the login page.

### 3.3 Admin link in footer

If **Settings → Show admin login link (shield) in footer** is enabled, a small shield icon in the footer links to the login page. Only admins can turn this on or off.

---

## 4. User roles (Staff vs Admin)

There are **two roles** for people who log into the admin:

| Role   | Can do | Cannot do |
|--------|--------|-----------|
| **Staff** | View dashboard; create and **edit** posts; view categories; **moderate comments** (approve/delete) | Delete posts; change Settings; add/delete categories; manage Staff (users) |
| **Admin** | Everything Staff can do, plus: **delete posts**, **Settings**, **add/delete categories**, **Staff** (add/edit users, set role, active, password) | — |

- **Readers** don’t have accounts; they only use the public site.
- Staff see “Staff” in the top bar; Admins see “Admin” and get extra menu items: **Settings** and **Staff**.

---

## 5. Admin dashboard

**URL:** `https://yoursite.com/admin/dashboard.php`

**What you see:**

- **Stats cards** – Total posts, total categories, total comments.
- **Shortcuts** – Add New Post, Posts, Comments, and (for Admin) Settings.
- **Recent posts** – Last 10 posts (all statuses), with **Status** shown as **draft**, **scheduled**, or **published**, and scheduled date if applicable. Link to Edit.
- **Most viewed** – Top 10 **publicly visible** posts by view count. Link to Edit.

---

## 6. Posts – create, edit, schedule

### 6.1 Posts list

**URL:** `https://yoursite.com/admin/posts.php`

- Table of **all** posts: title, category, author, **status** (draft / scheduled / published), scheduled date (if scheduled), featured, sponsored, views, date, actions.
- **Actions:** View (only for published/live posts), Edit, Delete (Admin only).
- “Add New Post” button at the top.

### 6.2 Add new post

**URL:** `https://yoursite.com/admin/add-post.php`

**Fields and options:**

- **Title** (required) – Used to generate the URL slug (e.g. “My Article” → `my-article`).
- **Summary** – Short text; can be shown above the main content on the post page.
- **Content** (required) – **Rich text editor (TinyMCE):** bold, italic, headings (H2–H4), bullet/numbered lists, links, blockquotes, **insert image** (upload or URL; uploads go to `assets/images/uploads/posts/`), **embed video** (e.g. YouTube via Media button). Content is stored as HTML.
- **Category** (required) – Dropdown of existing categories.
- **Publish**
  - **Publish Now** – Post is published and **visible immediately**.
  - **Save as Draft** – Post is not visible on the site.
  - **Schedule for Later** – Post is “published” but only becomes **visible** at the chosen **date and time**. A date/time picker appears when you select this.
- **Featured** – Check to use this post as the **hero** on the homepage (only one featured at a time in practice).
- **Sponsored** – Check to show a “Sponsored” badge on the post.
- **Tags** – Comma-separated (e.g. `tech, news`). Tags are created automatically if they don’t exist.
- **Meta Title / Meta Description** – For SEO; used in the post’s `<title>` and meta description and for sharing.
- **Featured image** – One image per post (shown on the post page and in cards). Upload: JPEG, PNG, GIF, WebP.

**Save Post** – Submits the form. TinyMCE content is saved into the textarea before submit so the server receives the full HTML. If something fails (e.g. missing title or category), you stay on the form and see error messages.

### 6.3 Edit post

**URL:** `https://yoursite.com/admin/edit-post.php?id=123`

- Same fields as Add post; form is filled with the existing post.
- **Publish** options reflect current state: Draft, Publish now, or Schedule (with existing scheduled time if set).
- **Delete** button – Only visible to **Admin**; goes to a confirmation page then deletes the post and its tags.

### 6.4 Delete post (Admin only)

**URL:** `https://yoursite.com/admin/delete-post.php?id=123`

- Confirmation page; on confirm, the post and its tag links are deleted. Redirects back to the posts list.

### 6.5 Scheduled publishing – how it works

- **Draft** – `status = draft`. Never shown on the public site.
- **Publish now** – `status = published`, `published_at = NULL`. Visible immediately.
- **Schedule for later** – `status = published`, `published_at =` chosen datetime. The post appears on the site **only when** the server time is past `published_at`.

Every public listing (homepage, category, search, single post, sitemap, view count, comments) uses this rule: show only posts that are **published** and (`published_at` is NULL or `published_at` is in the past).

---

## 7. Categories

**URL:** `https://yoursite.com/admin/categories.php`

- **Staff** – Can **view** the list (e.g. to choose a category when writing a post). Cannot add or delete.
- **Admin** – Can **add** a category (name; slug is auto-generated) and **delete** a category. Deletion is blocked if any post uses that category.

Categories appear in the main menu and on the category page (`category.php?slug=...`).

---

## 8. Comments moderation

**URL:** `https://yoursite.com/admin/comments.php`

- Table: post title (link), author name and email, comment snippet, **status** (pending/approved), date, actions.
- **Approve** – Changes status to “approved”; the comment then appears on the public post page.
- **Delete** – Removes the comment. Both actions use POST and CSRF.

Only **approved** comments are shown on the front. New comments from the public are stored as “pending”.

---

## 9. Settings (Admin only)

**URL:** `https://yoursite.com/admin/settings.php`

All values are stored in the `settings` table (single row). Only **Admin** can open this page.

**Sections:**

- **Site** – Site name, site description (e.g. tagline).
- **Contact** – Contact email (used for the contact form and as fallback “from” for emails), phone.
- **Social links** – Facebook, Twitter, Instagram, YouTube URLs (used in the footer).
- **Footer** – Footer text (e.g. copyright), and **Show admin login link (shield) in footer** (yes/no).
- **Ad placements** – Four text areas for HTML/ad code:
  - **Header ad** – Below main nav; header (including this ad) is sticky.
  - **Sidebar ad** – In the sidebar on listing and post pages.
  - **In-article ad** – Above article content on the single post page.
  - **Homepage ad** – Between “Latest News” and category sections on the homepage.

There is no “site logo” upload in the UI; the site uses **`assets/images/feyfaylogo.png`** by default. Logo and favicon are both that image unless you change the code or add a logo URL in settings.

---

## 10. Staff management (Admin only)

**URL:** `https://yoursite.com/admin/users.php`

- **Add user** – Name, username, email, password, role (Staff or Admin). Passwords are stored hashed (e.g. `password_hash`).
- **Table** – All users with name, username, email, role, active (yes/no), joined date.
- **Edit** (per user) – Change **role** (Staff/Admin), **Active** (uncheck to block login), **New password** (optional; leave blank to keep current).

Only **Admin** can access this page. Deactivating a user prevents them from logging in.

---

## 11. Contact form and newsletter

### 11.1 Contact form

- **Where:** Contact page (`contact.php`).
- **Flow:** Visitor fills name, email, optional subject, message → Submit → PHP sends an email to the address in **Settings → Contact email** (or a default). Reply-To is the visitor’s email.
- **Testing with MailHog:** In `includes/config.php`, set `MAIL_SMTP_HOST` to `'localhost'` and `MAIL_SMTP_PORT` to `1025`. Run MailHog and open `http://localhost:8025` to see caught emails. For production, clear `MAIL_SMTP_HOST` (or set to `''`) to use the server’s normal mail.

### 11.2 Newsletter

- **Where:** Sidebar (e.g. homepage, post page) and Contact page (same form with hidden “newsletter” field).
- **Flow:** Visitor enters email → Submit → Email is stored in `subscribers` (no duplicate emails). Message: “Thank you for subscribing” or “You are already subscribed.”
- **Data:** Stored only in the database; no automatic emails are sent from the CMS. You can export or use the list for your own newsletter tool.

---

## 12. Ads and monetization

- **Settings → Ad placements** – Paste HTML or script for each zone (header, sidebar, in-article, homepage). They are output as-is in the layout.
- **Sponsored posts** – In Add/Edit Post, check “Sponsored” to show a “Sponsored” badge on the post and in cards. No automatic ad injection; it’s a label only.

---

## 13. SEO and sitemap

- **Per post:** Meta title and meta description (Add/Edit Post). Used for `<title>`, meta description, and Open Graph / Twitter Card when sharing.
- **Canonical URL** – Set on public pages to avoid duplicate content.
- **Sitemap** – `https://yoursite.com/sitemap.php` lists homepage, static pages, categories, and **all publicly visible posts** (published and past scheduled time). Format is XML for search engines.
- **robots.txt** – Points crawlers to the sitemap. Edit the `Sitemap:` line to your real domain.

Submit `sitemap.php` in Google Search Console and Bing Webmaster Tools for indexing.

---

## 14. Installation and configuration

### 14.1 Requirements

- PHP 7.4+ (PDO MySQL, sessions, file uploads)
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx) or PHP built-in server

### 14.2 Fresh install

1. **Database**  
   Create database and tables:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```
   Or in MySQL: `source /path/to/FeyFay/sql/schema.sql`

2. **Database config**  
   Edit `includes/db.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

3. **Uploads**  
   Create and make writable:
   ```bash
   mkdir -p assets/images/uploads/posts
   chmod 755 assets/images/uploads/posts
   ```

4. **Run the site**  
   - **Built-in server:** `php -S localhost:8000` in the project folder, then open `http://localhost:8000`
   - **Apache/Nginx:** Point document root to the project folder.

### 14.3 Existing database (migration)

If you already have a FeyFay database from an older version:

```bash
mysql -u your_user -p feyfay_media < sql/migrate_production.sql
```

This adds missing columns (e.g. `is_sponsored`, `ads_homepage`, `show_admin_link`, `published_at`, `is_active`, role migration to staff/admin) and indexes. Safe to run; it checks before adding.

### 14.4 Default admin login

- **URL:** `https://yoursite.com/admin/login.php`
- **Username:** `admin`
- **Password:** `password`

**Change the password in production:** Generate a hash in PHP (`echo password_hash('your_new_password', PASSWORD_DEFAULT);`) and run:

```sql
UPDATE users SET password = 'paste_hash_here' WHERE username = 'admin';
```

### 14.5 Mail (contact form)

- **Production:** Set **Settings → Contact email**. For PHP `mail()` to work, the server must be able to send mail (or use an SMTP relay).
- **Development with MailHog:** In `includes/config.php` set `MAIL_SMTP_HOST` to `'localhost'` and `MAIL_SMTP_PORT` to `1025`. Leave `MAIL_SMTP_HOST` empty for production if you use PHP `mail()`.

---

## 15. File and folder structure

```
FeyFay/
├── admin/
│   ├── includes/
│   │   ├── header.php    # Admin layout head + nav
│   │   └── footer.php    # Admin layout footer + TinyMCE when needed
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── posts.php         # List posts
│   ├── add-post.php
│   ├── edit-post.php
│   ├── delete-post.php   # Admin only
│   ├── categories.php
│   ├── comments.php
│   ├── settings.php      # Admin only
│   ├── users.php         # Staff management, Admin only
│   └── upload-image.php  # TinyMCE inline image upload
├── ajax/
│   ├── comment.php      # Submit comment (JSON)
│   ├── increment-view.php # Count post view (JSON)
│   └── search.php       # Optional JSON search
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── admin.css
│   │   └── responsive.css
│   ├── js/
│   │   ├── main.js
│   │   └── admin.js
│   └── images/
│       ├── feyfaylogo.png  # Logo and favicon
│       └── uploads/posts/  # Featured + inline images
├── includes/
│   ├── config.php        # Paths, session, mail SMTP, constants
│   ├── db.php            # PDO connection
│   ├── auth.php          # Login check, roles, CSRF
│   ├── functions.php     # Helpers, post queries, visibility, etc.
│   ├── header.php        # Public layout head + header
│   ├── footer.php        # Public layout footer
│   ├── sidebar.php       # Trending, recent, newsletter, ads
│   └── pagination.php
├── sql/
│   ├── schema.sql       # Full DB schema
│   └── migrate_production.sql
├── index.php            # Homepage
├── post.php             # Single post
├── category.php         # Category listing
├── search.php           # Search results
├── about.php
├── contact.php          # Contact form + newsletter
├── 404.php
├── sitemap.php          # XML sitemap
├── robots.txt
├── config.php           # Optional; can load includes/config.php
├── README.md            # Technical overview
└── USER-GUIDE.md        # This file
```

---

## 16. Database tables

| Table        | Purpose |
|-------------|---------|
| **users**   | Admin and staff: name, username, email, password (hashed), role (staff/admin), is_active. |
| **categories** | Category name and slug. |
| **posts**   | title, slug, summary, content (HTML), image, category_id, author_id, status (draft/published), **published_at** (NULL or datetime for scheduling), is_featured, is_sponsored, views, meta_title, meta_description, created_at, updated_at. |
| **tags**    | Tag name and slug. |
| **post_tags** | Links posts to tags (many-to-many). |
| **comments** | post_id, name, email, message, status (pending/approved), created_at. |
| **settings** | Single row: site_name, site_logo, site_description, contact_email, phone, facebook, twitter, instagram, youtube, footer_text, ads_header, ads_sidebar, ads_article, ads_homepage, show_admin_link. |
| **subscribers** | Newsletter emails (unique). |

---

## 17. Security and best practices

- **Passwords** – Stored with `password_hash`; checked with `password_verify`.
- **Sessions** – HttpOnly, SameSite, optional Secure on HTTPS; session ID regenerated on login.
- **CSRF** – All important forms (login, settings, posts, categories, comments, users, delete post) use a CSRF token.
- **SQL** – Queries use PDO prepared statements.
- **Output** – User content is escaped with `htmlspecialchars` (e.g. `e()`); post content is output as HTML only where intended (article body).
- **Uploads** – Allowed types: JPEG, PNG, GIF, WebP. Paths and filenames are controlled to avoid overwriting.
- **Roles** – Every admin page checks login; Settings, Staff, delete post, and category add/delete also require Admin.

---

## 18. Troubleshooting

**“Save Post” does nothing**  
- The Content field uses TinyMCE. The form now saves TinyMCE content before submit and does not rely on the browser “required” check for content. If it still fails, check the browser console for errors and ensure no other script is preventing form submit.

**Contact form emails not received**  
- Set **Settings → Contact email**.  
- On localhost, use MailHog and set `MAIL_SMTP_HOST` / `MAIL_SMTP_PORT` in `includes/config.php`.  
- On a live server, ensure the server can send mail (or configure SMTP in code).

**Scheduled post not showing**  
- A post only appears when `published_at` is in the past (or NULL for “Publish now”). Check the server time and the scheduled date/time. Status must be “published” and `published_at <= NOW()`.

**Logo or favicon not showing**  
- Logo and favicon use `assets/images/feyfaylogo.png`. Ensure the file exists and the path is correct (e.g. `base_url('assets/images/feyfaylogo.png')`).

**Staff can’t see Settings or Staff menu**  
- Only **Admin** sees those. Log in with an admin account or change the user’s role in Admin → Staff.

**404 on post or category**  
- Post: slug must match exactly; post must be published and (if scheduled) already live.  
- Category: slug must exist in the categories table.

---

*This guide covers all main functions of FeyFay Media. For technical details and code references, see **README.md**.*
