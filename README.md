# 🌟 Donation Platform - Helping People Help People

A sophisticated full-stack fundraising and donation platform designed for transparency, ease of use, and efficient campaign management. Built with a modern **React.js** frontend and a robust **Laravel** backend.

---

## ✨ Key Features

### 👤 For Users (Creators & Donors)
- **Campaign Creation**: Easily start a fundraising campaign with a title, description, and target amount.
- **Personal Dashboard**: Track your own campaigns and their funding progress in real-time.
- **Public Campaign Pages**: Elegant, shareable campaign pages with live progress bars and donation history.
- **Integrated Donation Flow**: Secure donation interface supporting multiple payment methods.
- **Status Tracking**: Visual indicators for `Pending`, `Approved`, and `Rejected` statuses.

### 🛡️ Admin Power Panel
- **Comprehensive Statistics**: High-level overview of total campaigns, active requests, and total funds raised.
- **Advanced Campaign Review**:
  - **Details Modal**: In-depth review of campaign stories and creator contact info.
  - **Dynamic Approval**: Set or adjust target amounts directly during the approval process.
  - **Approval Workflow**: Streamlined Approve/Reject functionality with immediate feedback.
- **Role-Based Security**: Protected routes and restricted actions based on user roles (Admin vs. Regular User).

---

## 🛠️ Tech Stack

### Frontend
- **React.js**: Modern UI with Functional Components and Hooks.
- **React Router**: Seamless client-side navigation.
- **Context API**: Global state management for Authentication.
- **Premium CSS**: Custom modern styling with glassmorphism, responsive grids, and dynamic transitions.

### Backend
- **Laravel 10+**: RESTful API architecture.
- **PHP 8.1+**: Optimized performance.
- **MySQL**: Relational data storage.
- **Sanctum**: Secure API token-based authentication.

---

## 🚀 Installation & Setup

### 1. Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
# Configure your database in .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### 2. Frontend (React)
```bash
cd frontend
npm install
npm start
```

---

## 🛣️ API Reference

| Action | Endpoint | Method | Auth Required |
| :--- | :--- | :--- | :--- |
| **Login** | `/api/login` | `POST` | No |
| **Register** | `/api/register` | `POST` | No |
| **List Campaigns** | `/api/campaigns` | `GET` | No |
| **Create Campaign** | `/api/campaigns` | `POST` | Yes |
| **Update Status** | `/api/campaigns/{id}/status` | `PUT` | Admin |
| **Delete Campaign** | `/api/campaigns/{id}` | `DELETE` | Owner |
| **Donate** | `/api/donations` | `POST` | No |

---

## 🎨 UI Aesthetics
The platform features a premium user interface designed to inspire trust and engagement:
- **Dynamic Headers**: Auto-updating copyright years and status-aware navigation.
- **Rich Status Badges**: Color-coded indicators for quick data scanning.
- **Smooth Modals**: Elegant popup interfaces for donation forms and admin reviews.
- **Mobile Responsive**: Fully optimized for desktops, tablets, and smartphones.

---

## 📄 License
© 2025 Donation Platform. Designed for impact.