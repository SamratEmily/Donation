import React from 'react';
import { formatAmount, formatDate } from '../utils/formatters';

const CampaignTable = ({ 
  campaigns, 
  title = "All Campaigns", 
  onToggleStatus,
  onDelete,
  emptyStateAction,
  isAdmin = false
}) => {
  return (
    <div className="campaigns-table">
      <h3>{title}</h3>
      {campaigns.length === 0 ? (
        <div className="empty-state">
          <p>No campaigns found.</p>
          {emptyStateAction}
        </div>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Campaign</th>
              <th>Creator</th>
              <th>Progress</th>
              <th>Status</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {campaigns.map((campaign) => {
              const progressPercentage =
                (campaign.current_amount / campaign.target_amount) * 100;
              const isCompleted =
                campaign.current_amount >= campaign.target_amount;

              return (
                <tr key={campaign.id}>
                  <td>
                    <div className="campaign-info">
                      <strong>{campaign.title}</strong>
                      <br />
                      <small>{campaign.slug}</small>
                    </div>
                  </td>
                  <td>
                    <div>
                      <strong>{campaign.creator_name}</strong>
                      <br />
                      <small>{campaign.creator_email}</small>
                      {campaign.creator_phone && (
                        <>
                          <br />
                          <small>{campaign.creator_phone}</small>
                        </>
                      )}
                    </div>
                  </td>
                  <td>
                    <div className="progress-info">
                      <div className="progress-bar small">
                        <div
                          className="progress-fill"
                          style={{
                            width: `${Math.min(100, progressPercentage)}%`,
                            backgroundColor: isCompleted ? "#28a745" : "",
                          }}
                        ></div>
                      </div>
                      <div className="amounts-small">
                        {formatAmount(campaign.current_amount)} /{" "}
                        {formatAmount(campaign.target_amount)}
                      </div>
                      <div className="percentage">
                        {progressPercentage.toFixed(1)}%
                        {isCompleted && (
                          <span className="completed-badge">✓ Completed</span>
                        )}
                      </div>
                    </div>
                  </td>
                  <td>
                    <span
                      className={`status-badge ${campaign.status}`}
                    >
                      {campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1)}
                    </span>
                  </td>
                  <td>
                    <small>{formatDate(campaign.created_at)}</small>
                  </td>
                  <td>
                    <div className="action-buttons">
                      {/* Admin-only approval buttons */}
                      {isAdmin && (
                        <>
                          {campaign.status === 'pending' && (
                            <>
                              <button
                                className="approve-btn"
                                onClick={() => onToggleStatus(campaign, 'approved')}
                                title="Approve campaign"
                              >
                                ✓ Approve
                              </button>
                              <button
                                className="reject-btn"
                                onClick={() => onToggleStatus(campaign, 'rejected')}
                                title="Reject campaign"
                              >
                                ✕ Reject
                              </button>
                            </>
                          )}
                          {campaign.status === 'approved' && (
                            <button
                              className="reject-btn"
                              onClick={() => onToggleStatus(campaign, 'rejected')}
                              title="Reject campaign"
                            >
                              ✕ Reject
                            </button>
                          )}
                          {campaign.status === 'rejected' && (
                            <button
                              className="approve-btn"
                              onClick={() => onToggleStatus(campaign, 'approved')}
                              title="Approve campaign"
                            >
                              ✓ Approve
                            </button>
                          )}
                        </>
                      )}
                      
                      {/* User-only delete button */}
                      {!isAdmin && onDelete && campaign.status !== 'approved' && (
                        <button
                          className="delete-btn"
                          onClick={() => {
                            if (window.confirm('Are you sure you want to delete this campaign?')) {
                              onDelete(campaign);
                            }
                          }}
                          title="Delete campaign"
                        >
                          🗑️ Delete
                        </button>
                      )}
                      
                      {campaign.status === 'approved' && (
                      <button
                        className="view-btn"
                        onClick={() =>
                          window.open(`/campaign/${campaign.slug}`, "_blank")
                        }
                        title="View campaign"
                      >
                        👁️ View
                      </button>
                      )}
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      )}
    </div>
  );
};

export default CampaignTable;
