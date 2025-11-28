# Campaign Approval Workflow

## Overview
This document describes the campaign approval workflow that has been implemented in the Donation Platform. Users can now create campaigns that require admin approval before being publicly visible.

## Features Implemented

### 1. Database Changes
- **New Column**: Added `status` column to `campaigns` table with three possible values:
  - `pending` - Default status when a campaign is created
  - `approved` - Campaign is approved and publicly visible
  - `rejected` - Campaign is rejected by admin

### 2. Backend Changes

#### Migration
- File: `database/migrations/2025_11_27_135334_add_status_to_campaigns_table.php`
- Adds `status` enum column with default value 'pending'

#### Model Updates
- File: `app/Models/Campaign.php`
- Added `status` to fillable fields
- Removed `getStatusAttribute()` accessor (status is now a real column)

#### Controller Updates
- File: `app/Http/Controllers/CampaignController.php`

**Key Changes:**
1. **index()** - Only shows approved campaigns publicly
2. **store()** - Sets status to 'pending' and is_active to false by default
3. **showBySlug()** - Only shows approved campaigns
4. **updateStatus()** - New method (admin only) to approve/reject campaigns
   - Accepts status parameter: 'pending', 'approved', or 'rejected'
   - Syncs `is_active` with status (true for approved, false otherwise)
   - Optional target_amount parameter

#### Routes
- File: `routes/api.php`
- Changed: `POST /campaigns/{campaign}/toggle-status` → `PUT /campaigns/{campaign}/status`

### 3. Frontend Changes

#### API Service
- File: `frontend/src/services/api.js`
- Updated: `toggleStatus()` → `updateStatus(id, status, data)`
- New signature accepts explicit status parameter

#### Admin Panel
- File: `frontend/src/components/AdminPanel.js`

**New Tab Structure:**
1. **Campaign Requests** - Shows pending campaigns (default tab)
2. **Approved** - Shows approved campaigns
3. **Rejected** - Shows rejected campaigns
4. **Recent Donations** - Shows all donations

**Key Features:**
- Counts displayed in tab labels
- Separate views for each status
- Admin can approve/reject campaigns with optional target amount update

#### Campaign Table
- File: `frontend/src/components/CampaignTable.js`

**Status-Based Actions:**
- **Pending campaigns**: Show "Approve" and "Reject" buttons
- **Approved campaigns**: Show "Reject" button
- **Rejected campaigns**: Show "Approve" button
- All campaigns: Show "View" button

**Status Badges:**
- Color-coded badges for each status (pending/approved/rejected)

#### Styling
- File: `frontend/src/App.css`
- Added styles for status badges (pending, approved, rejected)
- Added styles for approve/reject buttons

### 4. User Flow

#### For Regular Users:
1. User creates a campaign via "Create Campaign" form
2. Campaign is created with status = 'pending' and is_active = false
3. User sees message: "Campaign created successfully and sent for approval"
4. Campaign appears in their dashboard but is not publicly visible
5. User waits for admin approval

#### For Admins:
1. Admin logs into Admin Dashboard
2. Sees "Campaign Requests" tab with pending campaigns count
3. Reviews pending campaigns
4. Can approve or reject each campaign:
   - **Approve**: Optionally update target amount, campaign becomes public
   - **Reject**: Campaign is marked as rejected
5. Approved campaigns appear on home page
6. Can change status later (approve rejected campaigns or reject approved ones)

### 5. Public Visibility Rules

**Home Page (Campaign List):**
- Only shows campaigns with status = 'approved'

**Campaign Detail Page (by slug):**
- Only shows approved campaigns
- Returns 403 error for pending/rejected campaigns

## API Endpoints

### Update Campaign Status (Admin Only)
```
PUT /api/campaigns/{id}/status
Authorization: Bearer {token}

Request Body:
{
  "status": "approved|rejected|pending",
  "target_amount": 50000 (optional)
}

Response:
{
  "success": true,
  "message": "Campaign status updated to approved",
  "data": { campaign object }
}
```

## Testing the Feature

1. **Create a campaign as a regular user:**
   - Login as regular user
   - Go to "Create Campaign"
   - Fill form and submit
   - Verify message says "sent for approval"

2. **Approve campaign as admin:**
   - Login as admin
   - Go to "Admin Dashboard"
   - Click "Campaign Requests" tab
   - See pending campaign
   - Click "Approve"
   - Optionally update target amount
   - Verify campaign moves to "Approved" tab

3. **Verify public visibility:**
   - Logout or use incognito
   - Go to home page
   - Verify approved campaign appears
   - Verify pending/rejected campaigns don't appear

4. **Test rejection:**
   - As admin, reject a campaign
   - Verify it moves to "Rejected" tab
   - Verify it doesn't appear on home page

5. **Test re-approval:**
   - As admin, approve a rejected campaign
   - Verify it moves to "Approved" tab
   - Verify it appears on home page

## Notes

- The `is_active` field is now synced with status (approved = active)
- Target amount can be updated when approving a campaign
- All status changes are admin-only operations
- Regular users can only see their own campaigns in their dashboard
- Public users only see approved campaigns
