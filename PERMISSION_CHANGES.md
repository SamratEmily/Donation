# Permission and UX Updates

## Overview
This document outlines the permission and user experience improvements made to the campaign management system.

## Changes Implemented

### 1. User Permission Restrictions

#### Regular Users (Non-Admin)
**Can Do:**
- ✅ View their own campaigns
- ✅ Delete their own campaigns (with confirmation)
- ✅ View campaign details

**Cannot Do:**
- ❌ Approve or reject campaigns (including their own)
- ❌ Change campaign status
- ❌ View other users' campaigns (except approved ones on home page)

#### Admin Users
**Can Do:**
- ✅ View all campaigns (pending, approved, rejected)
- ✅ Approve pending campaigns
- ✅ Reject pending/approved campaigns
- ✅ Re-approve rejected campaigns
- ✅ Update target amount when approving
- ✅ View campaign details

**Cannot Do:**
- ❌ Delete campaigns (to prevent accidental data loss)

### 2. Component Updates

#### CampaignTable Component
**New Props:**
- `isAdmin` (boolean, default: false) - Determines which actions to show
- `onDelete` (function) - Callback for delete action (user only)

**Action Buttons Logic:**
```javascript
// Admin sees: Approve/Reject buttons based on status
if (isAdmin) {
  - Pending: Show "Approve" and "Reject"
  - Approved: Show "Reject"
  - Rejected: Show "Approve"
}

// User sees: Delete button
if (!isAdmin && onDelete) {
  - Show "Delete" with confirmation dialog
}

// Everyone sees: View button
```

#### AdminPanel Component
**Props Passed:**
```javascript
<CampaignTable 
  campaigns={campaigns} 
  onToggleStatus={updateCampaignStatus}
  isAdmin={true}  // Admin sees approval buttons
/>
```

#### UserDashboard Component
**Props Passed:**
```javascript
<CampaignTable 
  campaigns={campaigns} 
  onDelete={deleteCampaign}
  isAdmin={false}  // User sees delete button
/>
```

**Functions:**
- Removed: `toggleCampaignStatus()` and `promptForTargetAmount()`
- Added: `deleteCampaign()` - Deletes campaign with API call

### 3. User Experience Improvements

#### After Campaign Creation
**Old Behavior:**
- Form resets
- Success message shown
- User stays on create page

**New Behavior:**
- Form resets
- Success message: "Campaign created successfully and sent for approval!"
- **Automatically redirects to home page** using `navigate('/')`
- User can immediately see approved campaigns (when approved by admin)

#### Delete Confirmation
- Users see confirmation dialog: "Are you sure you want to delete this campaign?"
- Prevents accidental deletions
- Success message shown after deletion

### 4. UI Updates

#### New Button Styles
**Delete Button:**
```css
.delete-btn {
  background-color: #dc3545; /* Red */
  color: white;
  /* Hover effect includes slight transform */
}
```

**Permission-Based Display:**
- Admin Panel: Shows approve/reject buttons with green/red colors
- User Dashboard: Shows delete button with red color
- Both: Show view button with gray color

### 5. Statistics Updates

#### UserDashboard Stats
- `activeCampaigns` now counts campaigns with `status === 'approved'` (not `is_active`)
- Consistent with new approval workflow

#### AdminPanel Stats
- `activeCampaigns` counts approved campaigns
- Shows counts in tab labels: `Campaign Requests (3)`

## Testing Checklist

### As Regular User:
- [ ] Create a campaign and verify redirect to home page
- [ ] Check campaign appears in "My Dashboard" with pending status
- [ ] Verify delete button is visible but no approve/reject buttons
- [ ] Delete a campaign and confirm it's removed
- [ ] Try to view campaign detail (should show 403 if not approved)

### As Admin:
- [ ] Log into Admin Dashboard
- [ ] See pending campaigns in "Campaign Requests" tab
- [ ] Approve a campaign (optionally update target amount)
- [ ] Verify campaign moves to "Approved" tab
- [ ] Check campaign appears on home page for public
- [ ] Reject a campaign and verify it moves to "Rejected" tab
- [ ] Re-approve a rejected campaign

### Public View:
- [ ] Home page shows only approved campaigns
- [ ] Cannot access pending/rejected campaign detail pages
- [ ] Can donate to approved campaigns

## API Endpoints Used

### User Actions:
```
DELETE /api/campaigns/{id}
Authorization: Bearer {token}
```

### Admin Actions:
```
PUT /api/campaigns/{id}/status
Authorization: Bearer {token}

Request Body:
{
  "status": "approved|rejected|pending",
  "target_amount": 50000 (optional)
}
```

## Files Modified

### Frontend:
1. `/frontend/src/components/CampaignTable.js`
   - Added `isAdmin` and `onDelete` props
   - Conditional rendering of action buttons

2. `/frontend/src/components/AdminPanel.js`
   - Pass `isAdmin={true}` to CampaignTable

3. `/frontend/src/components/UserDashboard.js`
   - Removed status toggle functionality
   - Added `deleteCampaign()` function
   - Pass `isAdmin={false}` and `onDelete` to CampaignTable
   - Updated stats to use `status === 'approved'`

4. `/frontend/src/components/CreateCampaign.js`
   - Import `useNavigate` from react-router-dom
   - Add redirect to home page after creation
   - Update success message

5. `/frontend/src/App.css`
   - Added `.delete-btn` styles

### Backend:
- No backend changes needed (existing delete endpoint available)

## Security Notes

1. **Authorization on Backend:**
   - Delete endpoint checks user ownership or admin role
   - Status update endpoint requires admin role
   - Backend enforces all permissions (frontend is UI only)

2. **Frontend Permission Display:**
   - `isAdmin` prop determines UI visibility
   - Users cannot see admin buttons (even if they inspect element)
   - All actions require proper backend authorization

3. **Data Protection:**
   - Delete confirmation prevents accidental deletions
   - Campaigns can only be deleted by owner or admin (backend enforced)
   - Status changes are admin-only (backend enforced)

## User Flow Diagrams

### Regular User Flow:
```
Create Campaign → Success Message → Redirect to Home
                                   ↓
                              View Approved Campaigns
                                   ↓
My Dashboard → View My Campaigns → Delete or View
```

### Admin Flow:
```
Admin Dashboard → Campaign Requests Tab
                       ↓
                 Pending Campaigns
                       ↓
           Approve (+ optional target) or Reject
                       ↓
        Moves to Approved/Rejected Tab
                       ↓
              Visible on Home Page (if approved)
```

## Migration Notes

### Existing Campaigns:
- All existing campaigns have `status = 'pending'` after migration
- Admin needs to approve them for public visibility
- Users cannot activate their own campaigns anymore

### User Experience:
- Users will notice they can no longer activate/deactivate campaigns
- Users now have delete functionality instead
- Clearer separation between user and admin capabilities
