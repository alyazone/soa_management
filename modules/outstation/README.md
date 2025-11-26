# Outstation Leave Tracking Module

## Overview
This module tracks outstation leave applications for employees. It automatically calculates eligibility for leave claims based on the duration of the trip (stays of 2 nights or more qualify).

## Features

### For All Staff
- **Submit Applications**: Fill out forms before going outstation
- **Track Applications**: View status and history of all submissions
- **Modify Pending Applications**: Update details before approval
- **Automatic Calculation**: System calculates nights stayed and claim eligibility
- **Status Tracking**: Real-time updates on application status

### For Managers & Admins
- **Approve/Reject Applications**: Review and process staff applications
- **Dashboard Analytics**: Visual insights with charts and statistics
- **Staff Overview**: Track who has claimable applications
- **Reports**: Monitor outstation trends and popular destinations

## File Structure

```
/modules/outstation/
├── index.php                    # Main listing page for all applications
├── view.php                     # Detailed view of single application
├── edit.php                     # Edit application (pending only)
├── application_form.php         # Create new application form
├── view_application.php         # Legacy view (deprecated)
├── dashboard.php                # Manager dashboard with charts
├── database_schema.sql          # Database table definitions
├── setup_database.php           # One-time database setup script
├── api/
│   ├── create_application.php   # API endpoint to create applications
│   ├── update_application.php   # API endpoint to update applications
│   └── approve_application.php  # API endpoint for approvals/rejections
└── README.md                    # This file
```

## Database Tables

### outstation_applications
Main table storing all outstation leave applications.

**Key Fields:**
- `application_number`: Unique identifier (OSL-YYYY-####)
- `staff_id`: Foreign key to staff table
- `destination`: Where the staff member is traveling
- `departure_date` / `return_date`: Travel dates
- `total_nights`: Calculated nights stayed
- `is_claimable`: Boolean (1 if nights >= 2)
- `status`: Pending/Approved/Rejected/Cancelled/Completed
- `approved_by`: Staff ID of approver
- `rejection_reason`: Reason for rejection (if applicable)

### outstation_claims
Tracks actual claims submitted against approved applications (future implementation).

### outstation_settings
Configurable settings like minimum nights, allowance amounts, etc.

## Business Logic

### Claimability Rules
1. **Minimum Nights**: Stay must be 2 nights or more
2. **Calculation**: `return_date - departure_date = total_nights`
3. **Auto-calculation**: System automatically determines eligibility
4. **Approval Required**: Must be approved before claiming

### Workflow
1. **Employee submits** application before trip
2. **System calculates** nights and claimability
3. **Manager reviews** and approves/rejects
4. **Employee can modify** pending applications if plans change
5. **Dashboard tracks** who has claimable applications

## Setup Instructions

### First Time Setup
1. Run the database setup script:
   ```
   Visit: /modules/outstation/setup_database.php
   ```

2. Verify tables were created:
   - outstation_applications
   - outstation_claims
   - outstation_settings

3. Navigate to the module via sidebar:
   - **Sidebar → Outstation Leave → My Applications**

### Permissions
- **All Staff**: Can create and view their own applications
- **Managers**: Can view all applications, approve/reject, access dashboard
- **Admins**: Full access including system settings

## Usage Examples

### Creating an Application
1. Navigate to **Outstation Leave → New Application**
2. Fill in required fields:
   - Purpose of trip
   - Destination
   - Departure and return dates
   - Transportation mode
3. System automatically calculates nights and shows if claimable
4. Submit for approval

### Approving Applications (Manager)
1. Navigate to **Outstation Leave → My Applications**
2. Click on pending application
3. Review details
4. Click "Approve" or "Reject"
5. Provide reason if rejecting

### Viewing Dashboard (Manager)
1. Navigate to **Outstation Leave → Dashboard**
2. View statistics:
   - Total applications
   - Pending approvals
   - Approval rate
   - Claimable applications
3. Analyze charts:
   - Monthly trends
   - Status distribution
   - Top destinations
4. Review staff table to see who has pending claims

## Design Patterns

### Modern Aesthetics
- Matches existing modules (Client, Supplier, SOA)
- Tailwind CSS for layout
- Responsive design for mobile
- Font Awesome icons
- AOS animations for smooth transitions
- Chart.js for interactive charts

### Color Scheme
- **Primary (Blue)**: #3b82f6 - Main actions
- **Success (Green)**: #10b981 - Approved, claimable
- **Warning (Yellow)**: #f59e0b - Pending
- **Danger (Red)**: #ef4444 - Rejected, errors
- **Info (Cyan)**: #06b6d4 - Information, completed

### Status Colors
- **Pending**: Orange/Yellow
- **Approved**: Green
- **Rejected**: Red
- **Cancelled**: Gray
- **Completed**: Cyan

## API Endpoints

### POST `/api/create_application.php`
Creates a new outstation leave application.

**Required Fields:**
- application_number
- staff_id
- purpose
- destination
- departure_date
- return_date
- transportation_mode

**Response:**
```json
{
  "success": true,
  "message": "Application submitted successfully",
  "data": {
    "application_id": 1,
    "application_number": "OSL-2025-0001",
    "total_nights": 3,
    "is_claimable": 1
  }
}
```

### POST `/api/update_application.php`
Updates an existing pending application.

**Restrictions:**
- Only pending applications can be edited
- Only owner or admin/manager can edit
- Recalculates nights and claimability

### POST `/api/approve_application.php`
Approves or rejects an application.

**Actions:**
- `approve`: Sets status to Approved
- `reject`: Sets status to Rejected (requires rejection_reason)

**Permissions:** Admin or Manager only

## Customization

### Changing Claimability Rules
Edit values in `outstation_settings` table:
```sql
UPDATE outstation_settings
SET setting_value = '2'
WHERE setting_key = 'minimum_nights_claimable';
```

### Adding New Statuses
Modify the ENUM in `outstation_applications` table:
```sql
ALTER TABLE outstation_applications
MODIFY COLUMN status ENUM('Pending','Approved','Rejected','Cancelled','Completed','NewStatus');
```

### Customizing Dashboard
Edit `/modules/outstation/dashboard.php`:
- Modify SQL queries for different statistics
- Add new charts using Chart.js
- Adjust date ranges and filters

## Troubleshooting

### Database Not Found Error
**Solution**: Run `/modules/outstation/setup_database.php`

### Permission Denied
**Solution**: Check user position in session. Some features require Admin/Manager role.

### Application Not Saving
**Solution**: Check browser console for JavaScript errors and server error logs

### Charts Not Displaying
**Solution**: Ensure Chart.js CDN is loading. Check browser console.

## Future Enhancements

### Planned Features
1. **Claims Submission**: Link applications to actual claim forms
2. **Document Uploads**: Attach receipts and supporting documents
3. **Email Notifications**: Notify staff of approval/rejection
4. **Expense Tracking**: Track actual vs estimated costs
5. **Recurring Applications**: Template for frequent destinations
6. **Mobile App**: Native mobile application
7. **PDF Export**: Generate application PDFs
8. **Calendar Integration**: Sync with staff calendars

### Integration Opportunities
- Link with Claims Management module
- Integration with HR systems
- Financial system for expense reimbursement
- Project management for client-related trips

## Support

For issues or questions:
1. Check this README
2. Review `/CLAUDE.md` for system-wide guidance
3. Check application logs
4. Contact system administrator

## Version History

- **v1.0** (2025-11-18): Initial release
  - Basic CRUD operations
  - Automatic claimability calculation
  - Manager dashboard with charts
  - Modern responsive design

## Credits

Built following the architectural patterns established in the SOA Management System.
Compatible with existing modules: Client Management, Supplier Management, SOA Management, Claims Management.
