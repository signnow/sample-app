# HR Onboarding System - SignNow Sample

This sample demonstrates a complete HR onboarding workflow using SignNow SDK for electronic document signing. The application allows HR managers to create onboarding document packages from templates, prefill employee information, and manage the signing process for multiple stakeholders including the employee, HR manager, and employer.

## Use Case Overview

The HR Onboarding System addresses the common business need for streamlined employee onboarding processes. When a new employee joins a company, multiple documents need to be signed by different parties (employee, HR manager, employer) in a coordinated manner. This sample shows how to:

- Collect employee and stakeholder information
- Select relevant onboarding documents from templates
- Prefill documents with employee data
- Create a document group for coordinated signing
- Send invites to all parties with proper role assignments
- Track signing progress and download completed documents

**Target Audience**: HR departments, recruitment teams, and organizations implementing digital onboarding processes.

## Scenario Description

A typical HR onboarding workflow involves multiple stakeholders and documents. This sample demonstrates a complete flow where:

1. **HR Manager** initiates the onboarding process by entering employee and stakeholder information
2. **System** creates documents from templates and prefills employee data
3. **Multiple Parties** (Employee, HR Manager, Employer) receive signing invites
4. **System** tracks signing progress and provides download access to completed documents

The workflow ensures all required documents are properly signed by the appropriate parties before the onboarding process is considered complete.

### Step-by-step:

1. **Form Page**: HR manager enters employee name, employee email, HR manager email, and employer email
2. **Document Selector Page**: HR manager selects which onboarding documents to include (I9 Form, NDA, Employee Contract)
3. **Backend Processing**: System clones selected templates, prefills employee data, creates document group, and sends invites
4. **Status Page**: HR manager can monitor signing progress and download completed documents
5. **Signing Process**: Each stakeholder receives email invites and signs documents through SignNow interface
6. **Completion**: All parties complete signing, and final merged document becomes available for download

## Technical Flow

| Action | Responsibility | Code Location |
|--------|---------------|---------------|
| Form submission | Frontend validation and data collection | `initFormPage()` in index.blade.php |
| Document selection | Frontend UI for template selection | `initDocumentSelector()` in index.blade.php |
| Document group creation | Backend API call to create documents from templates | `createDocumentGroup()` in SampleController.php |
| Field prefilling | Backend API call to populate employee data | `prefillFields()` in SampleController.php |
| Invite creation | Backend API call to send signing invites | `sendInvite()` in SampleController.php |
| Status tracking | Frontend polling and backend status retrieval | `checkStatus()` in index.blade.php |
| Document download | Backend API call to retrieve merged PDF | `downloadDocumentGroupFile()` in SampleController.php |

## Sequence of Function Calls

1. **Frontend Form Submission** (`initFormPage()`)
   - Validates form data
   - Redirects to document selector with form data as query parameters

2. **Document Selection** (`initDocumentSelector()`)
   - Handles template selection UI
   - Calls backend with `action: 'create-invite'`

3. **Backend Document Creation** (`createInvite()`)
   - Validates required fields
   - Calls `createDocumentGroup()` to clone templates and prefill data
   - Calls `sendInvite()` to create signing invites
   - Returns document group ID

4. **Document Group Creation** (`createDocumentGroup()`)
   - Iterates through selected template IDs
   - Calls `createDocumentFromTemplate()` for each template
   - Calls `prefillFields()` to populate employee data
   - Calls `createDocumentGroupFromDocuments()` to combine documents

5. **Invite Creation** (`sendInvite()`)
   - Retrieves document group metadata
   - Creates invite actions for each document and role
   - Creates invite emails for all stakeholders
   - Sends GroupInvitePost request

6. **Status Tracking** (`checkStatus()`)
   - Polls backend with `action: 'invite-status'`
   - Calls `getDocumentGroupSignersStatus()` to retrieve current status
   - Updates UI based on completion status

7. **Document Download** (click handler in `initStatusPage()`)
   - Calls backend without action parameter (defaults to download)
   - Calls `downloadDocumentGroupFile()` to retrieve merged PDF
   - Triggers browser download

## Page Flow Documentation

### HTML Container Pages

1. **Form Page** (`<div id="form-page">`)
   - **Purpose**: Collect employee and stakeholder information
   - **Fields**: Employee name, employee email, HR manager email, employer email
   - **Navigation**: Submits form data via query parameters to document selector

2. **Document Selector Page** (`<div id="document-selector">`)
   - **Purpose**: Allow selection of onboarding document templates
   - **Content**: List of available templates (I9 Form, NDA, Employee Contract)
   - **Navigation**: Calls backend API and redirects to status page with document group ID

3. **Status Page** (`<div id="status-page">`)
   - **Purpose**: Monitor signing progress and provide download access
   - **Features**: Status polling, refresh button, download functionality
   - **Completion**: Calls `parent.postMessage()` to notify parent application

### Page Navigation Flow

```
Form Page → Document Selector Page → Status Page
```

- **Form Page → Document Selector**: Data passed via query parameters (`?page=document-selector&employee_name=...`)
- **Document Selector → Status Page**: Backend API call creates document group, then redirect with document group ID
- **Status Page**: Monitors signing progress and provides download access

### Data Flow Between Pages

- **Form Data**: Passed via URL query parameters between HTML container pages
- **Document Group ID**: Generated by backend and passed to status page for tracking
- **Status Updates**: Retrieved via API calls from status page
- **Download**: Triggered by clicking download button in status page

## Template Info

The sample uses three predefined templates with specific field mappings:

| Template ID | Document Name | Prefilled Fields |
|-------------|---------------|------------------|
| `940989288b8b4c62a950b908333b5b21efd6a174` | I9 Form | Name, Email, Text Field 2, Text Field 156 |
| `a4f523d0cb234ffc99b0badc9e6f59111f76abc2` | NDA | Name, Email, Text Field 2, Text Field 156 |
| `1a12d3e00a54457ca1bf7bde5fa37d38ede866ed` | Employee Contract | Name, Email, Text Field 2, Text Field 156 |

**Role Assignments**:
- **Contract Preparer**: HR Manager
- **Employee**: Employee
- **Employer**: Employer

## Configuration

### Required Environment Variables
- `SIGNNOW_CLIENT_ID`: SignNow API client ID
- `SIGNNOW_CLIENT_SECRET`: SignNow API client secret
- `SIGNNOW_USERNAME`: SignNow account username
- `SIGNNOW_PASSWORD`: SignNow account password

### Setup Instructions
1. Configure SignNow API credentials in environment variables
2. Ensure template IDs are valid and accessible
3. Verify template field names match the prefilling logic
4. Test the workflow with valid email addresses

## Quick Start (TL;DR)

1. **Access the sample**: Navigate to `/samples/HROnboardingSystem`
2. **Fill the form**: Enter employee name, employee email, HR manager email, and employer email
3. **Select documents**: Choose which onboarding documents to include
4. **Submit**: System creates document group and sends invites
5. **Monitor progress**: Check status page for signing progress
6. **Download**: Access completed documents when all parties have signed

**Expected Flow**: Form → Document Selection → Status Monitoring → Download

----

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

----

## Disclaimer

This sample is for educational and demonstration purposes only. For production use, consider:

- **Security**: Implement proper authentication and authorization
- **Validation**: Add comprehensive input validation and sanitization
- **Error Handling**: Implement robust error handling and user feedback
- **Customization**: Adapt template IDs and field mappings for your specific use case
- **Compliance**: Ensure the workflow meets your organization's legal and compliance requirements
