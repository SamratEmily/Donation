import React, { useState, useEffect, useCallback } from 'react';
import { campaignAPI } from '../services/api';

const CampaignList = ({ onCampaignSelect }) => {
  const [campaigns, setCampaigns] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [search, setSearch] = useState('');
  
  const fetchCampaigns = useCallback(async (pageNum = 1, searchQuery = '') => {
    try {
      setLoading(true);
      const response = await campaignAPI.getAll(pageNum, searchQuery);
      
      if (response.data.success || response.data.data) {
        setCampaigns(response.data.data);
        const meta = response.data.meta;
        setTotalPages(meta.last_page);
        setPage(meta.current_page);
      }
    } catch (err) {
      setError('Failed to load campaigns: ' + (err.response?.data?.message || err.message));
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, []);

  // Initial load and Search effect
  useEffect(() => {
    const delayDebounce = setTimeout(() => {
      fetchCampaigns(1, search);
    }, 500);

    return () => clearTimeout(delayDebounce);
  }, [search, fetchCampaigns]);

  // Handle page change
  const handlePageChange = (newPage) => {
    if (newPage >= 1 && newPage <= totalPages) {
      fetchCampaigns(newPage, search);
       window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const formatAmount = (amount) => {
    return new Intl.NumberFormat('en-BD', {
      style: 'currency',
      currency: 'BDT',
      minimumFractionDigits: 0
    }).format(amount);
  };

  const clearSearch = () => {
    setSearch('');
  };

  // Skeleton Component
  const CampaignSkeleton = () => (
    <div className="campaign-card skeleton-card">
      <div className="skeleton skeleton-title"></div>
      <div className="skeleton skeleton-text"></div>
      <div className="skeleton skeleton-text" style={{ width: '80%' }}></div>
      <div className="skeleton skeleton-text" style={{ width: '60%', marginBottom: '20px' }}></div>
      
      <div className="skeleton skeleton-bar"></div>
      
      <div className="skeleton-amounts">
        <div className="skeleton skeleton-amount"></div>
        <div className="skeleton skeleton-amount"></div>
      </div>
      
      <div className="skeleton skeleton-meta"></div>
      <div className="skeleton skeleton-meta"></div>
      
      <div className="skeleton skeleton-btn"></div>
    </div>
  );

  // Pagination UI Component
  const renderPagination = () => {
    if (totalPages <= 1) return null;

    const pages = [];
    const maxVisiblePages = 5;
    
    let startPage = Math.max(1, page - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

    if (endPage - startPage + 1 < maxVisiblePages) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }

    // Previous Button
    pages.push(
      <button 
        key="prev"
        onClick={() => handlePageChange(page - 1)}
        disabled={page === 1}
        className={`pagination-btn ${page === 1 ? 'disabled' : ''}`}
      >
        Previous
      </button>
    );

    // First Page
    if (startPage > 1) {
      pages.push(
        <button key={1} onClick={() => handlePageChange(1)} className="pagination-btn">
          1
        </button>
      );
      if (startPage > 2) {
        pages.push(<span key="dots-start" className="pagination-dots">...</span>);
      }
    }

    // Page Numbers
    for (let i = startPage; i <= endPage; i++) {
        pages.push(
          <button 
            key={i} 
            onClick={() => handlePageChange(i)} 
            className={`pagination-btn ${page === i ? 'active' : ''}`}
          >
            {i}
          </button>
        );
    }

    // Last Page
    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        pages.push(<span key="dots-end" className="pagination-dots">...</span>);
      }
      pages.push(
        <button key={totalPages} onClick={() => handlePageChange(totalPages)} className="pagination-btn">
          {totalPages}
        </button>
      );
    }

    // Next Button
    pages.push(
      <button 
        key="next"
        onClick={() => handlePageChange(page + 1)}
        disabled={page === totalPages}
        className={`pagination-btn ${page === totalPages ? 'disabled' : ''}`}
      >
        Next
      </button>
    );

    return <div className="pagination-container">{pages}</div>;
  };

  if (error && campaigns.length === 0) return <div className="error-message">{error}</div>;

  return (
    <div className="campaign-list">
      <div className="header-actions" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '20px', flexWrap: 'wrap', gap: '15px' }}>
        <h2>Active Donation Campaigns</h2>
        
        <div className="search-box" style={{ flex: '1', maxWidth: '400px', display: 'flex', position: 'relative' }}>
          <input
            type="text"
            placeholder="Search campaigns..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            style={{
              width: '100%',
              padding: '10px 15px',
              borderRadius: '20px',
              border: '1px solid #ddd',
              fontSize: '14px',
              paddingRight: '30px'
            }}
          />
          {search && (
            <button 
              onClick={clearSearch}
              style={{
                position: 'absolute',
                right: '10px',
                top: '50%',
                transform: 'translateY(-50%)',
                background: 'none',
                border: 'none',
                cursor: 'pointer',
                color: '#999',
                fontSize: '16px'
              }}
            >
              &times;
            </button>
          )}
        </div>
      </div>

      {loading ? (
        <div className="campaigns-grid">
          {[...Array(6)].map((_, i) => <CampaignSkeleton key={i} />)}
        </div>
      ) : campaigns.length === 0 ? (
        <div className="no-results" style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
          <p>No campaigns found matching your search.</p>
          {search && <button onClick={clearSearch} className="text-btn">Clear Search</button>}
        </div>
      ) : (
        <>
          <div className="campaigns-grid">
            {campaigns.map((campaign) => (
              <div key={campaign.id} className="campaign-card">
                <h3>{campaign.title}</h3>
                <p className="campaign-description">{campaign.description}</p>
                
                <div className="campaign-stats">
                  <div className="progress-bar">
                    <div 
                      className="progress-fill" 
                      style={{ 
                        width: `${Math.min(100, (campaign.current_amount / campaign.target_amount) * 100)}%`,
                        backgroundColor: campaign.current_amount >= campaign.target_amount ? '#28a745' : ''
                      }}
                    ></div>
                  </div>
                  
                  <div className="amounts">
                    <span className="current-amount">
                      Raised: {formatAmount(campaign.current_amount)}
                    </span>
                    <span className="target-amount">
                      Goal: {formatAmount(campaign.target_amount)}
                    </span>
                  </div>
                  
                  <div className="campaign-meta">
                    <p><strong>Organizer:</strong> {campaign.creator_name}</p>
                    <p><strong>Payment Method:</strong> {campaign.payment_type.toUpperCase()}</p>
                    {campaign.creator_phone && (
                      <p><strong>Phone:</strong> {campaign.creator_phone}</p>
                    )}
                  </div>
                </div>
                
                {campaign.is_active ? (
                  <button 
                    className="donate-btn"
                    onClick={() => onCampaignSelect(campaign)}
                  >
                    {campaign.current_amount >= campaign.target_amount ? 'Goal Achieved - Donate More' : 'Donate Now'}
                  </button>
                ) : (
                  <button className="donate-btn disabled" disabled>
                    Campaign Closed
                  </button>
                )}
                
                <div className="campaign-link">
                  <small>Share: /campaign/{campaign.slug}</small>
                </div>
              </div>
            ))}
          </div>

          {renderPagination()}
          
          <style>{`
            .pagination-container {
              display: flex;
              justify-content: center;
              align-items: center;
              gap: 5px;
              margin-top: 30px;
              flex-wrap: wrap;
            }
            .pagination-btn {
              padding: 8px 16px;
              border: 1px solid #ddd;
              background: white;
              cursor: pointer;
              border-radius: 4px;
              transition: all 0.2s;
              color: #333;
            }
            .pagination-btn:hover:not(.disabled) {
              background: #f0f0f0;
              border-color: #bbb;
            }
            .pagination-btn.active {
              background: #667eea;
              color: white;
              border-color: #667eea;
            }
            .pagination-btn.disabled {
              background: #f9f9f9;
              color: #ccc;
              cursor: not-allowed;
            }
            .pagination-dots {
              color: #666;
              padding: 0 5px;
            }
            .text-btn {
              background: none;
              border: none;
              color: #007bff;
              text-decoration: underline;
              cursor: pointer;
              margin-top: 10px;
            }
          `}</style>
        </>
      )}
    </div>
  );
};

export default CampaignList;