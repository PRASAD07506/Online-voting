# 🗳️ Online Voting System

A secure and user-friendly **Online Voting System** built using PHP and MySQL.
This project allows users to register, verify identity, and vote digitally, while admins manage elections and results.

---

## 🚀 Features

### 👤 User Features

* User Registration & Login
* Secure Authentication System
* Face Verification (if implemented)
* Vote Casting
* Candidate Request Submission
* Dashboard for users

### 🛠️ Admin Features

* Admin Login Panel
* Add / Manage Candidates
* Approve Candidate Requests
* Manage Elections
* View Voting Results
* User Management

---

## 🏗️ Project Structure

```
php-project/
│── admin/              # Admin panel files
│── assets/             # CSS, JS, Images
│── auth/               # Authentication system
│── config/             # Database & helpers
│── user/               # User dashboard & features
│── uploads/            # Uploaded files (faces/images)
│── sql/                # Database SQL file
│── index.php           # Main entry point
│── style.css           # Styling
```

---

## ⚙️ Technologies Used

* PHP
* MySQL
* HTML, CSS, JavaScript
* XAMPP (Local Server)

---

## 🛠️ Installation (Local Setup)

1. Install XAMPP

2. Copy project folder to:

   ```
   C:\xampp\htdocs\
   ```

3. Start:

   * Apache
   * MySQL

4. Open phpMyAdmin:

   * Create a database
   * Import file from:

     ```
     sql/hosting_setup.sql
     ```

5. Update database config:

   ```
   config/db.php
   ```

6. Run project:

   ```
   http://localhost/php-project
   ```

---

## 🔐 Security Features

* CSRF Protection
* Session Handling
* Authentication System
* Input Validation

---

## 📌 Future Improvements

* Email Verification
* OTP Authentication
* Blockchain-based voting
* Improved UI/UX
* Deployment on cloud

---

## 📄 License

This project is for educational purposes.

---

## 🙌 Author

**Prasad**
GitHub: https://github.com/PRASAD07506

---

⭐ If you like this project, give it a star!
