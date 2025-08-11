# ISV with Form and One Click Send - Basic Pre-fill

This sample demonstrates how to create a streamlined document signing workflow for ISV (Independent Software Vendor) applications using SignNow SDK. The sample shows how to collect user information through a custom form, create a document group from a template, prefill document fields with user data, send invites to signers, and provide status tracking functionality.

## Use Case Overview

This sample addresses the common requirement of integrating electronic signature capabilities into existing business applications using direct API calls with basic field prefill. It demonstrates a streamlined workflow where:

- Users fill out a simple form with their information
- The system automatically creates a document group from a template
- Document fields are pre-filled with user data (Name and Email)
- Recipients are added to the document group with the provided email
- Users can track the status and download completed documents

This approach is ideal for applications that want to provide seamless document signing experiences within their platform with basic field prefill capabilities.

## Scenario Description

### Step 1: Form Collection
- User opens the application and sees a simple form
- Form contains Name and Email fields
- User fills out the form and clicks "Continue"
- System validates the input and proceeds to document preparation

### Step 2: Document Preparation
- System automatically creates a document group from a predefined template
- Document fields are pre-filled with the user's name and email
- Recipients are added to the document group with the provided email
- User is redirected directly to status page

### Step 3: Status Tracking
- User can view the current status of the document group
- System provides download functionality for completed documents
- Status page shows creation and update timestamps

### Step-by-step:

1. **User fills out the form**: Enter name and email in the provided form fields
2. **Form submission**: Click "Continue" to submit the form data
3. **Document group creation**: System creates a document group from the template
4. **Field prefill**: Document fields are automatically filled with user data
5. **Recipient addition**: User's email is added as a recipient to the document group
6. **Invite sending**: System sends an email invite to the user
7. **Status page redirect**: User is automatically redirected to the status page
8. **Status monitoring**: User can monitor signing progress and download completed documents
9. **Document download**: Download the completed document when all signers have signed

## Page Flow Documentation

### HTML Container Pages
- **Form Page** (`<div id="form-page">`): User input form for collecting name and email
- **Status Page** (`<div id="status-page">`): Displays document status, signer information, and provides download functionality

### Page State Management
- **Query Parameters**: All pages use `?page=` parameter for routing
- **Data Persistence**: `document_group_id` is passed between pages via URL parameters
- **Error Handling**: Each page includes proper error handling and user feedback
- **Loading States**: All pages show loading indicators during API calls

### Page Navigation Flow
```
Form Page → Status Page
```

- **Form Page**: Collect user data, prepare document group, prefill fields, send invite
- **Status Page**: Show completion status and provide download

### Data Flow Between Pages
- **Form Page → Status Page**: `document_group_id` passed via URL parameters

## Technical Flow

| Action | Responsibility | Code Location |
|--------|---------------|---------------|
| Form submission | Frontend validation and API call | `initFormPage()` in index.blade.php |
| Document group creation | Backend SDK integration | `createDocumentGroupFromTemplate()` in SampleController.php |
| Field prefill | Backend SDK document update | `updateDocumentFields()` in SampleController.php |
| Recipient addition | Backend SDK recipient management | `sendInvite()` in SampleController.php |
| Status tracking | Backend SDK status API | `getInviteStatus()` and `getDocumentGroupSignersStatus()` in SampleController.php |
| Document download | Backend SDK download API | `downloadDocumentGroup()` and `downloadDocumentGroupFile()` in SampleController.php |

## Sequence of Function Calls

1. **Frontend Form Submission**
   - `initFormPage()` → Form validation
   - `fetch('/api/samples/ISVWithFormAndOneClickSendBasicPrefill')` → POST with action: 'prepare_dg'

2. **Backend Document Preparation**
   - `prepareDocumentGroup()` → Main orchestration
   - `createDocumentGroupFromTemplate()` → Create DG from template
   - `updateDocumentFields()` → Prefill document fields with name and email
   - `sendInvite()` → Add recipients and send invites

3. **Frontend Status Tracking**
   - Redirect to status page with document_group_id
   - `initStatusPage()` → Initialize status page
   - `updateStatuses()` → Poll for status updates
   - Display recipient status and download button

4. **Frontend Document Download**
   - Download request to backend
   - File download to user's device
   - Notify parent that sample is finished

## Template Info

### Document Group Template
- **Template ID**: `6e79b9e6f9624984a7f054a7171d1644d0fb9934`
- **Template Name**: "Membership Program Agreement"
- **Template Type**: Document Group Template (DGT)

### Document Fields
- **Name Field**: Pre-filled with user's name from form
- **Email Field**: Pre-filled with user's email from form

### Recipient Configuration
- **Role**: "Recipient 1"
- **Email**: User's email from form
- **Invite**: Automatically sent to user's email address
- **Redirect URL**: Returns to status page after signing

## Configuration

### Required Environment Variables
- `SIGNNOW_CLIENT_ID`: SignNow API client ID
- `SIGNNOW_CLIENT_SECRET`: SignNow API client secret
- `SIGNNOW_USERNAME`: SignNow account username
- `SIGNNOW_PASSWORD`: SignNow account password

### Setup Instructions
1. Configure SignNow API credentials in environment variables
2. Ensure the document group template is accessible
3. Verify the template contains "Name" and "Email" fields
4. Test the sample with valid email addresses

## Quick Start (TL;DR)

1. **Open the sample**: Navigate to `/samples/ISVWithFormAndOneClickSendBasicPrefill`
2. **Fill out the form**: Enter name and email
3. **Submit the form**: Click "Continue" to create document group and send invite
4. **Check status**: Monitor signing progress on status page
5. **Download document**: Download completed document when all signers have signed

### URL Patterns
- **Form Page**: `/samples/ISVWithFormAndOneClickSendBasicPrefill`
- **Status Page**: `/samples/ISVWithFormAndOneClickSendBasicPrefill?page=status-page&document_group_id={id}`

### Expected Flow
1. User fills form → Document group created → Fields pre-filled → Recipients added → Status tracking → Download

____

## Ready to build eSignature integrations with SignNow API? Get the SignNow extension for GitHub Copilot

Use AI-powered code suggestions to generate SignNow API code snippets in your IDE with GitHub Copilot. Get examples for common integration tasks—from authentication and sending documents for signature to handling webhooks, and building branded workflows.

###  **🚀 Why use SignNow with GitHub Copilot**

* **Relevant code suggestions**: Get AI-powered, up-to-date code snippets for SignNow API calls. All examples reflect the latest API capabilities and follow current best practices.
* **Faster development**: Reduce time spent searching documentation.
* **Fewer mistakes**: Get context-aware guidance aligned with the SignNow API.
* **Smooth onboarding**: Useful for both new and experienced developers working with the API.

### **Prerequisites:**

1\. GitHub Copilot installed and enabled.  
2\. SignNow account. [Register here](https://www.signnow.com/developers)

### ⚙️ **How to use it**

1\. Install the [SignNow extension](https://github.com/apps/signnow).

2\. Start your prompts with [@signnow](https://github.com/signnow) in the Copilot chat window. The first time you use the extension, you may need to authorize it.

3\. Enter a prompt describing the integration scenario.   
Example: @signnow Generate a Java code example for sending a document group to two signers.

4\. Modify the generated code to match your app’s requirements—adjust parameters, headers, and workflows as needed.

### **Troubleshooting**
**The extension doesn’t provide code examples for the SignNow API**

Make sure you're using `@signnow` in the Copilot chat and that the extension is installed and authorized.
____

## Disclaimer

This sample is designed for educational and demonstration purposes. Before using in production:

- Implement proper error handling and validation
- Add security measures for user authentication
- Configure proper logging and monitoring
- Test thoroughly with your specific use case
- Ensure compliance with data protection regulations
- Customize the workflow to match your business requirements 