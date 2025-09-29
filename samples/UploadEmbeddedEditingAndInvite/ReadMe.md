# Upload Embedded Editing and Invite Sample

This sample demonstrates a complete workflow for uploading a PDF document to SignNow, creating an embedded editing interface, and sending signing invites to recipients. It combines document upload, embedded editing, and invite management capabilities using the SignNow SDK.

## Use Case Overview

This sample is designed for scenarios where you need to:
- Upload a PDF document to SignNow from user's local device
- Allow users to edit the document using SignNow's embedded editor
- Send signing invites to designated recipients
- Track signing status and download completed documents

**Target Audience**: Developers implementing document workflows that require upload, editing, and signing capabilities in a single integrated flow.

## Scenario Description

This sample demonstrates a business workflow where a user uploads a document from their local device, edits it using SignNow's embedded editor, and then sends it for signing. The workflow includes:

1. **Document Upload**: User selects and uploads a PDF file from their device to SignNow
2. **Embedded Editing**: Provide an embedded editor interface for document modification
3. **Invite Management**: Send signing invites to designated recipients
4. **Status Tracking**: Monitor signing progress and download completed documents

### Step-by-step:

1. **Upload Page**: User selects a PDF file from their device and uploads it to SignNow
2. **Embedded Editor**: SignNow interface for document editing (external URL)
3. **Invite Page**: User provides signer information and sends invite
4. **Status Page**: Monitor signing progress and download completed document

### Page Flow Documentation:

- **HTML Container Pages**:
  - `<div id="upload-page">` - Initial document upload interface
  - `<div id="invite-page">` - Form for entering signer information and managing recipients
  - `<div id="status-page">` - Status tracking and download interface

- **Embedded SignNow Pages**:
  - Embedded Editor URL - External SignNow interface for document editing
  - Redirects back to invite page after editing completion

## Technical Flow

| Action | Responsibility | Code Location |
|--------|---------------|---------------|
| Document Upload | Backend | `uploadAndCreateDocumentGroup()` |
| Generate Edit Link | Backend | `createEmbeddedEditLink()` |
| Send Signing Invite | Backend | `createInvite()` |
| Track Status | Backend | `getInviteStatus()` |
| Download Document | Backend | `downloadDocument()` |

### Page Navigation Flow:

- **HTML Container → Embedded SignNow**: Redirect to external SignNow editor URL
- **Embedded SignNow → HTML Container**: Return via redirect URL with query parameters
- **HTML Container → HTML Container**: Direct navigation using `?page=` parameter

## Sequence of Function Calls

1. **Frontend**: `initUploadPage()` - Initiates document upload
2. **Backend**: `uploadAndCreateDocumentGroup()` - Uploads PDF and creates document group
3. **Frontend**: `initInvitePage()` - Provides access to embedded editor
4. **Backend**: `createEmbeddedEditLink()` - Generates embedded editor URL
5. **Frontend**: `initInvitePage()` - Collects signer information
6. **Backend**: `createInvite()` - Sends signing invite to recipient
7. **Frontend**: `initStatusPage()` - Monitors signing status
8. **Backend**: `getInviteStatus()` - Retrieves current signing status
9. **Backend**: `downloadDocument()` - Downloads completed document

### Page Navigation Sequence:

- Frontend page routing (`handlePages()` function)
- Backend embedded URL generation with redirects
- SignNow API calls for embedded interfaces
- Return flow from embedded pages to HTML containers

## Template Info

This sample allows users to upload their own PDF files from their local device. The uploaded document should contain appropriate roles (such as "Signer") for the invite functionality to work correctly.

**Document Requirements**:
- PDF format only
- Contains signing roles for invite functionality
- Compatible with SignNow's document processing
- File size within SignNow's limits

## Configuration

### Required Environment Variables

- `SIGNNOW_CLIENT_ID` - SignNow API client ID
- `SIGNNOW_CLIENT_SECRET` - SignNow API client secret
- `SIGNNOW_USERNAME` - SignNow account username
- `SIGNNOW_PASSWORD` - SignNow account password

### Setup Instructions

1. Configure SignNow API credentials in your environment
2. Run the application and navigate to the sample
3. Prepare a PDF file with signing roles for testing
4. Follow the workflow steps as described

## Quick Start (TL;DR)

1. **Start**: Navigate to `/samples/UploadEmbeddedEditingAndInvite`
2. **Upload**: Select a PDF file from your device and upload it
3. **Edit**: Click "Open Document Editor" to access embedded editor
4. **Invite**: Click "Send Signing Invite" to send invite
5. **Monitor**: Check status and download completed document

**URL Patterns**:
- Initial: `/samples/UploadEmbeddedEditingAndInvite`
- Invite: `/samples/UploadEmbeddedEditingAndInvite?page=invite-page&document_id={id}`
- Status: `/samples/UploadEmbeddedEditingAndInvite?page=status-page&document_id={id}`

**Expected Flow**: Select File → Upload → Edit → Invite → Sign → Download

## Disclaimer

This sample is designed for demonstration and educational purposes. For production use, consider:

- **Security**: Implement proper authentication and authorization
- **Error Handling**: Add comprehensive error handling and user feedback
- **Validation**: Validate all user inputs and file uploads
- **Customization**: Adapt the workflow to your specific business requirements
- **Performance**: Optimize for your expected load and usage patterns

The sample demonstrates core SignNow SDK functionality but may require additional security measures and customization for production deployment.