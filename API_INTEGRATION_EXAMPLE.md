# API Creation & Frontend Integration Example

This guide shows how APIs are created in Laravel backend and integrated with React frontend in this project.

## Complete Flow: Donation API Example

### 1. Backend: Laravel API (Create Endpoint)

#### Step 1: Define Route (`backend/routes/api.php`)

```php
use App\Http\Controllers\DonationController;

// Public donation routes
Route::post('donations', [DonationController::class, 'store']);
Route::get('donations', [DonationController::class, 'index']);

// Protected routes (requires authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('donations/{id}', [DonationController::class, 'show']);
    Route::put('donations/{id}', [DonationController::class, 'update']);
    Route::delete('donations/{id}', [DonationController::class, 'destroy']);
});
```

#### Step 2: Create Controller (`backend/app/Http/Controllers/DonationController.php`)

```php
<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    /**
     * Create a new donation
     */
    public function store(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'campaign_id' => 'required|exists:campaigns,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'nullable|email|max:255',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bkash,nagad,rocket,bank',
            'message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Create donation
        $donation = Donation::create(array_merge(
            $request->all(),
            ['status' => 'completed']
        ));

        // Return success response
        return response()->json([
            'success' => true,
            'data' => $donation->load('campaign'),
            'message' => 'Donation processed successfully'
        ], 201);
    }

    /**
     * Get all donations
     */
    public function index(Request $request)
    {
        $query = Donation::with('campaign');

        // Filter by campaign if provided
        if ($request->has('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        $donations = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $donations
        ]);
    }
}
```

#### Step 3: Model (`backend/app/Models/Donation.php`)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'campaign_id',
        'donor_name',
        'donor_email',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'message'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
```

---

### 2. Frontend: React Integration

#### Step 1: API Service (`frontend/src/services/api.js`)

```javascript
import axios from 'axios';

const API_BASE_URL = 'http://localhost:8000/api';

// Create axios instance
const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Add authentication token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle authentication errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// Donation API methods
export const donationAPI = {
  // Get all donations (optionally filter by campaign)
  getAll: (campaignId = null) => {
    const params = campaignId ? { campaign_id: campaignId } : {};
    return api.get('/donations', { params });
  },
  
  // Create new donation
  create: (data) => api.post('/donations', data),
  
  // Get donation by id
  getById: (id) => api.get(`/donations/${id}`),
  
  // Update donation
  update: (id, data) => api.put(`/donations/${id}`, data),
  
  // Delete donation
  delete: (id) => api.delete(`/donations/${id}`),
};

export default api;
```

#### Step 2: React Component (`frontend/src/components/DonationForm.js`)

```javascript
import React, { useState } from 'react';
import { donationAPI } from '../services/api';

const DonationForm = ({ campaign, onSuccess, onCancel }) => {
  const [formData, setFormData] = useState({
    donor_name: '',
    donor_email: '',
    amount: '',
    payment_method: 'bkash',
    message: 'This donation belongs to Mankind!'
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Prepare donation data
      const donationData = {
        ...formData,
        campaign_id: campaign.id
      };

      // Call API
      const response = await donationAPI.create(donationData);
      
      // Handle success
      if (response.data.success) {
        alert('Thank you for your donation for Mankind!');
        onSuccess(); // Callback to parent component
      }
    } catch (err) {
      // Handle error
      setError(err.response?.data?.message || 'Failed to process donation');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="donation-form">
      <h2>Donate to: {campaign.title}</h2>

      {error && <div className="error-message">{error}</div>}

      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <label htmlFor="donor_name">Your Name:</label>
          <input
            type="text"
            id="donor_name"
            name="donor_name"
            value={formData.donor_name}
            onChange={handleChange}
            required
          />
        </div>

        <div className="form-group">
          <label htmlFor="amount">Amount (BDT):</label>
          <input
            type="number"
            id="amount"
            name="amount"
            value={formData.amount}
            onChange={handleChange}
            required
            min="1"
          />
        </div>

        <div className="form-group">
          <label htmlFor="payment_method">Payment Method:</label>
          <select
            id="payment_method"
            name="payment_method"
            value={formData.payment_method}
            onChange={handleChange}
            required
          >
            <option value="bkash">bKash</option>
            <option value="nagad">Nagad</option>
            <option value="rocket">Rocket</option>
            <option value="bank">Bank Transfer</option>
          </select>
        </div>

        <div className="form-actions">
          <button type="button" onClick={onCancel}>
            Cancel
          </button>
          <button type="submit" disabled={loading}>
            {loading ? 'Processing...' : 'Donate Now'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default DonationForm;
```

#### Step 3: Using the Component

```javascript
import React, { useState } from 'react';
import DonationForm from './DonationForm';

const CampaignDetail = () => {
  const [showDonationForm, setShowDonationForm] = useState(false);
  const [campaign, setCampaign] = useState(null);

  const handleDonationSuccess = () => {
    setShowDonationForm(false);
    // Refresh campaign data to show updated amount
    fetchCampaign();
  };

  return (
    <div>
      <h1>{campaign?.title}</h1>
      
      <button onClick={() => setShowDonationForm(true)}>
        Donate Now
      </button>

      {showDonationForm && (
        <DonationForm 
          campaign={campaign}
          onSuccess={handleDonationSuccess}
          onCancel={() => setShowDonationForm(false)}
        />
      )}
    </div>
  );
};
```

---

## Request/Response Flow

### Creating a Donation

**Frontend Request:**
```javascript
const donationData = {
  campaign_id: 1,
  donor_name: "John Doe",
  donor_email: "john@example.com",
  amount: 500,
  payment_method: "bkash",
  message: "Great cause!"
};

const response = await donationAPI.create(donationData);
```

**HTTP Request:**
```
POST http://localhost:8000/api/donations
Content-Type: application/json

{
  "campaign_id": 1,
  "donor_name": "John Doe",
  "donor_email": "john@example.com",
  "amount": 500,
  "payment_method": "bkash",
  "message": "Great cause!"
}
```

**Backend Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "campaign_id": 1,
    "donor_name": "John Doe",
    "donor_email": "john@example.com",
    "amount": "500.00",
    "payment_method": "bkash",
    "status": "completed",
    "message": "Great cause!",
    "created_at": "2025-11-20T10:30:00.000000Z",
    "campaign": {
      "id": 1,
      "title": "Help Build School",
      "current_amount": "15500.00",
      "target_amount": "50000.00"
    }
  },
  "message": "Donation processed successfully"
}
```

---

## Key Concepts

### Backend (Laravel)

1. **Routes** - Define API endpoints in `routes/api.php`
2. **Controllers** - Handle request logic and return responses
3. **Models** - Interact with database
4. **Validation** - Validate incoming data
5. **Response Format** - Consistent JSON structure with `success`, `data`, `message`

### Frontend (React)

1. **API Service** - Centralized API calls using axios
2. **Interceptors** - Add auth tokens, handle errors globally
3. **State Management** - useState for form data and loading states
4. **Error Handling** - Try-catch blocks for API calls
5. **Callbacks** - Parent components notified of success/failure

### Authentication Flow

**Protected Routes:**
```javascript
// Backend
Route::middleware('auth:sanctum')->group(function () {
    Route::post('campaigns', [CampaignController::class, 'store']);
});

// Frontend - Token automatically added by interceptor
const response = await campaignAPI.create(campaignData);
```

---

## Testing the API

### Using Browser Console:
```javascript
// Test creating a donation
const testDonation = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/donations', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        campaign_id: 1,
        donor_name: "Test User",
        amount: 100,
        payment_method: "bkash"
      })
    });
    const data = await response.json();
    console.log(data);
  } catch (error) {
    console.error(error);
  }
};

testDonation();
```

### Using curl:
```bash
curl -X POST http://localhost:8000/api/donations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "campaign_id": 1,
    "donor_name": "Test User",
    "amount": 100,
    "payment_method": "bkash"
  }'
```

---

## Common Patterns

### GET Request (Fetch Data)
```javascript
// Frontend
const fetchDonations = async () => {
  const response = await donationAPI.getAll();
  setDonations(response.data.data);
};
```

### POST Request (Create Data)
```javascript
// Frontend
const createDonation = async (formData) => {
  const response = await donationAPI.create(formData);
  return response.data;
};
```

### PUT Request (Update Data)
```javascript
// Frontend
const updateDonation = async (id, updates) => {
  const response = await donationAPI.update(id, updates);
  return response.data;
};
```

### DELETE Request (Remove Data)
```javascript
// Frontend
const deleteDonation = async (id) => {
  await donationAPI.delete(id);
};
```

---

## Error Handling

### Backend Validation Errors:
```json
{
  "success": false,
  "errors": {
    "amount": ["The amount field is required."],
    "donor_name": ["The donor name field is required."]
  }
}
```

### Frontend Error Display:
```javascript
catch (err) {
  if (err.response?.data?.errors) {
    // Validation errors
    const errors = err.response.data.errors;
    setError(Object.values(errors).flat().join(', '));
  } else {
    // General error
    setError(err.response?.data?.message || 'Something went wrong');
  }
}
```
