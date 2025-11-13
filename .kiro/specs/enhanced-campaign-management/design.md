# Design Document

## Overview

This design document outlines the implementation of enhanced campaign management features and improved user experience for the donation platform. The solution builds upon the existing Laravel backend and React frontend architecture, adding new database tables, API endpoints, and UI components while maintaining backward compatibility.

## Architecture

### Database Schema Changes

#### New Tables

**campaign_categories**
```sql
- id (primary key)
- name (varchar, unique)
- slug (varchar, unique) 
- description (text, nullable)
- icon (varchar, nullable)
- created_at, updated_at
```

**campaign_tags**
```sql
- id (primary key)
- name (varchar, unique)
- slug (varchar, unique)
- usage_count (integer, default 0)
- created_at, updated_at
```

**campaign_tag_pivot**
```sql
- campaign_id (foreign key)
- tag_id (foreign key)
- created_at
```

**campaign_images**
```sql
- id (primary key)
- campaign_id (foreign key)
- filename (varchar)
- original_name (varchar)
- file_size (integer)
- mime_type (varchar)
- is_primary (boolean, default false)
- created_at, updated_at
```

**campaign_updates**
```sql
- id (primary key)
- campaign_id (foreign key)
- title (varchar)
- content (text)
- image_path (varchar, nullable)
- is_published (boolean, default true)
- created_at, updated_at
```

**campaign_analytics**
```sql
- id (primary key)
- campaign_id (foreign key)
- metric_type (enum: 'view', 'share', 'click')
- source (varchar, nullable)
- user_agent (text, nullable)
- ip_address (varchar, nullable)
- created_at
```

#### Modified Tables

**campaigns table additions**
```sql
- category_id (foreign key, nullable)
- end_date (datetime, nullable)
- featured_image (varchar, nullable)
- view_count (integer, default 0)
- share_count (integer, default 0)
```

### Backend API Design

#### New Controllers

**CategoryController**
- `GET /api/categories` - List all categories
- `POST /api/categories` - Create category (admin only)
- `PUT /api/categories/{id}` - Update category (admin only)
- `DELETE /api/categories/{id}` - Delete category (admin only)

**TagController**
- `GET /api/tags` - List all tags with autocomplete
- `GET /api/tags/popular` - Get most used tags
- `POST /api/tags` - Create new tag

**CampaignImageController**
- `POST /api/campaigns/{id}/images` - Upload campaign images
- `DELETE /api/campaigns/{id}/images/{imageId}` - Delete campaign image
- `PUT /api/campaigns/{id}/images/{imageId}/primary` - Set primary image

**CampaignUpdateController**
- `GET /api/campaigns/{id}/updates` - Get campaign updates
- `POST /api/campaigns/{id}/updates` - Create campaign update
- `PUT /api/campaigns/{id}/updates/{updateId}` - Edit campaign update
- `DELETE /api/campaigns/{id}/updates/{updateId}` - Delete campaign update

**AnalyticsController**
- `POST /api/campaigns/{id}/analytics` - Track campaign interaction
- `GET /api/campaigns/{id}/analytics` - Get campaign analytics (owner only)
- `GET /api/analytics/dashboard` - Get user dashboard analytics

#### Enhanced Existing Controllers

**CampaignController Updates**
- Enhanced `index()` with search, filtering, and pagination
- Updated `store()` and `update()` to handle categories, tags, and images
- New `share()` method to track social shares
- New `leaderboard()` method for donation rankings

**DonationController Updates**
- New `leaderboard()` method for top donors
- New `recent()` method for recent donations feed
- Enhanced privacy controls for donor visibility

### Frontend Component Architecture

#### New Components

**CampaignFilters**
- Category dropdown filter
- Tag multi-select filter
- Date range picker
- Funding status filter
- Location filter (future enhancement)

**CampaignSearch**
- Real-time search input
- Search suggestions dropdown
- Recent searches history

**ImageUploader**
- Drag-and-drop image upload
- Image preview and cropping
- Progress indicators
- Error handling

**CampaignGallery**
- Responsive image gallery
- Lightbox modal for full-size viewing
- Image navigation controls

**CampaignUpdates**
- Rich text editor for updates
- Update timeline display
- Email notification triggers

**SocialShareButtons**
- Platform-specific sharing buttons
- Share count display
- Custom share messages

**AnalyticsDashboard**
- Interactive charts using Chart.js
- Key metrics cards
- Export functionality
- Date range selectors

**DonationLeaderboard**
- Top donors list with privacy controls
- Anonymous donor handling
- Donation amount formatting

**RecentDonations**
- Real-time donation feed
- Donor privacy controls
- Campaign linking

#### Enhanced Existing Components

**CampaignList Updates**
- Integrated search and filters
- Pagination controls
- Loading states and skeletons
- Infinite scroll option

**CampaignCard Updates**
- Category and tag display
- Countdown timer for deadlines
- Image carousel
- Share buttons
- View count display

**CreateCampaign Updates**
- Category selection
- Tag input with autocomplete
- Image upload section
- End date picker
- Rich text editor for description

**CampaignDetail Updates**
- Image gallery
- Updates timeline
- Social sharing section
- Analytics preview (for owners)
- Donation leaderboard

## Components and Interfaces

### File Upload System

**Image Processing Pipeline**
1. Client-side validation and preview
2. Server-side validation and security checks
3. Image optimization and resizing using Intervention Image
4. Thumbnail generation (multiple sizes)
5. Cloud storage integration (AWS S3 or local storage)
6. Database record creation

**File Storage Structure**
```
storage/
├── campaigns/
│   ├── {campaign-id}/
│   │   ├── images/
│   │   │   ├── original/
│   │   │   ├── large/
│   │   │   ├── medium/
│   │   │   └── thumbnails/
│   │   └── updates/
│   │       └── images/
```

### Search and Filtering System

**Search Implementation**
- Full-text search using MySQL FULLTEXT indexes
- Elasticsearch integration for advanced search (future enhancement)
- Search result ranking based on relevance and popularity
- Search analytics and trending queries

**Filter System**
- URL-based filter state management
- Real-time filter application
- Filter combination logic
- Saved filter preferences

### Analytics System

**Data Collection**
- Page view tracking with user session management
- Social share tracking with UTM parameters
- Conversion funnel analysis
- Geographic data collection (with privacy compliance)

**Metrics Calculation**
- Real-time metrics using Redis caching
- Daily/weekly/monthly aggregations
- Conversion rate calculations
- Trend analysis and insights

## Data Models

### Enhanced Campaign Model

```php
class Campaign extends Model
{
    // Existing relationships
    public function donations() { return $this->hasMany(Donation::class); }
    public function user() { return $this->belongsTo(User::class); }
    
    // New relationships
    public function category() { return $this->belongsTo(CampaignCategory::class); }
    public function tags() { return $this->belongsToMany(CampaignTag::class); }
    public function images() { return $this->hasMany(CampaignImage::class); }
    public function updates() { return $this->hasMany(CampaignUpdate::class); }
    public function analytics() { return $this->hasMany(CampaignAnalytic::class); }
    
    // New accessors
    public function getIsExpiredAttribute() { /* Check end_date */ }
    public function getDaysRemainingAttribute() { /* Calculate remaining days */ }
    public function getPrimaryImageAttribute() { /* Get featured image */ }
    public function getTopDonorsAttribute() { /* Get leaderboard */ }
}
```

### New Model Classes

```php
class CampaignCategory extends Model
{
    public function campaigns() { return $this->hasMany(Campaign::class); }
}

class CampaignTag extends Model
{
    public function campaigns() { return $this->belongsToMany(Campaign::class); }
}

class CampaignImage extends Model
{
    public function campaign() { return $this->belongsTo(Campaign::class); }
}

class CampaignUpdate extends Model
{
    public function campaign() { return $this->belongsTo(Campaign::class); }
}

class CampaignAnalytic extends Model
{
    public function campaign() { return $this->belongsTo(Campaign::class); }
}
```

## Error Handling

### File Upload Errors
- File size validation with user-friendly messages
- File type validation with security checks
- Storage quota management
- Graceful degradation for upload failures

### Search and Filter Errors
- Invalid filter parameter handling
- Search timeout management
- Empty result state handling
- Filter reset functionality

### Analytics Errors
- Data collection failure handling
- Privacy compliance error management
- Analytics service unavailability handling

## Testing Strategy

### Backend Testing
- Unit tests for new models and relationships
- Feature tests for API endpoints
- File upload testing with mock storage
- Search and filter functionality testing
- Analytics data collection testing

### Frontend Testing
- Component unit tests with React Testing Library
- Integration tests for search and filter workflows
- File upload component testing
- Analytics dashboard testing
- Mobile responsiveness testing

### Performance Testing
- Database query optimization testing
- Image upload and processing performance
- Search response time testing
- Analytics data aggregation performance

## Security Considerations

### File Upload Security
- File type validation and MIME type checking
- Virus scanning integration
- File size limits and storage quotas
- Secure file serving with proper headers

### Data Privacy
- GDPR compliance for analytics data
- User consent management for tracking
- Data anonymization for public displays
- Secure data export functionality

### API Security
- Rate limiting for search and analytics endpoints
- Input validation and sanitization
- Authorization checks for sensitive operations
- CSRF protection for file uploads