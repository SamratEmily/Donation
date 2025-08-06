# Donation Platform

A full-stack donation/fundraising platform built with React.js frontend and Laravel backend.

## Features
- Create donation campaigns with custom links
- Display campaign details with progress tracking
- Multiple payment methods support
- User-friendly donation interface

## Tech Stack
- Frontend: React.js
- Backend: Laravel
- Database: MySQL/PostgreSQL
- Payment: Configurable payment gateways

## Setup Instructions

### Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### Frontend (React)
```bash
cd frontend
npm install
npm start
```

## API Endpoints
- GET /api/campaigns - List all campaigns
- POST /api/campaigns - Create new campaign
- GET /api/campaigns/{id} - Get campaign details
- POST /api/donations - Process donation

## Environment Variables
See .env.example files in both frontend and backend directories.