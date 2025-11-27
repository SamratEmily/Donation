import React from 'react';
import { formatAmount, formatDate } from '../utils/formatters';

const DonationTable = ({ donations, title = "Recent Donations" }) => {
  return (
    <div className="donations-table">
      <h3>{title}</h3>
      {donations.length === 0 ? (
        <div className="empty-state">
          <p>No donations received yet.</p>
        </div>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Donor</th>
              <th>Campaign</th>
              <th>Amount</th>
              <th>Payment Method</th>
              <th>Date</th>
              <th>Message</th>
            </tr>
          </thead>
          <tbody>
            {donations.slice(0, 50).map((donation) => (
              <tr key={donation.id}>
                <td>
                  <div>
                    <strong>{donation.donor_name}</strong>
                    {donation.donor_email && (
                      <>
                        <br />
                        <small>{donation.donor_email}</small>
                      </>
                    )}
                  </div>
                </td>
                <td>
                  <div>
                    <strong>{donation.campaign?.title || "N/A"}</strong>
                  </div>
                </td>
                <td>
                  <strong className="donation-amount">
                    {formatAmount(donation.amount)}
                  </strong>
                </td>
                <td>
                  <span className="payment-method">
                    {donation.payment_method.toUpperCase()}
                  </span>
                </td>
                <td>
                  <small>{formatDate(donation.created_at)}</small>
                </td>
                <td>
                  {donation.message ? (
                    <div className="donation-message">
                      "{donation.message}"
                    </div>
                  ) : (
                    <small className="no-message">No message</small>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
};

export default DonationTable;
