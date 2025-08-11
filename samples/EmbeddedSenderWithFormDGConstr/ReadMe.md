# Embedded Sender With Form DG - Construction Project Contract

This sample demonstrates how to create a streamlined document signing workflow for construction project contracts using SignNow SDK. The sample shows how to collect user information through a custom form, create a document group from a Construction Project Contract template, populate fields with user data, send the document for signing using embedded sending, and then proceed directly to signing with limited token authentication.

## Use Case Overview

This sample addresses the common requirement of integrating electronic signature capabilities into construction project management applications using embedded sending and signing workflows. It demonstrates a streamlined workflow where:

- Users fill out a simple form with their information
- The system automatically creates a document group from a Construction Project Contract template
- Document fields are populated with user data
- The document is prepared using embedded sending interface
- Users proceed directly to signing with limited token authentication
- Users can track the status and download completed documents

This approach is ideal for construction management applications that want to provide seamless document signing experiences within their platform without requiring users to leave their application, with a simplified workflow that eliminates intermediate signers.

## Scenario Description

### Step 1: Form Collection
- User opens the application and sees a simple form
- Form contains Name and Email fields
- User fills out the form and clicks "Continue"
- System validates the input and proceeds to document preparation

### Step 2: Document Preparation
- System automatically creates a document group from a Construction Project Contract template
- Document fields are populated with the user's name
- Recipients are added to the document group with the provided email
- User is redirected to embedded sending interface

### Step 3: Embedded Sending
- User is redirected to SignNow embedded sending interface
- User can review and configure the document for sending
- After completing embedded sending, user is redirected directly to status page

### Step 4: Status Tracking
- User can view the current status of the document group
- System provides download functionality for completed documents
- Status page shows creation and update timestamps

## Page Flow Documentation

### HTML Container Pages
- **Form Page** (`<div id="form-page">`): User input form for collecting name and email
- **Status Page** (`<div id="status-page">`): Displays document status, signer information, and provides download functionality

### Embedded SignNow Pages
- **Embedded Sending**: External SignNow URL for document preparation and sending configuration

### Page State Management
- **Query Parameters**: All pages use `?page=` parameter for routing
- **Data Persistence**: `document_group_id` is passed between pages via URL parameters
- **Error Handling**: Each page includes proper error handling and user feedback
- **Loading States**: All pages show loading indicators during API calls

### Page Navigation Flow
```
Form Page → Embedded Sending → Status Page
```

- **Form Page**: Collect user data, prepare document group
- **Embedded Sending**: SignNow interface for document preparation (external URL)
- **Status Page**: Show completion status and provide download

### Data Flow Between Pages
- **Form Page → Embedded Sending**: `document_group_id` passed via redirect URL
- **Embedded Sending → Status Page**: `document_group_id` returned via redirect URL

## Technical Flow

| Action | Responsibility | Code Location |
|--------|---------------|---------------|
| Form submission | Frontend validation and API call | `initFormPage()` in index.blade.php |
| Document group creation | Backend SDK integration | `createDocumentGroupFromTemplate()` in SampleController.php |
| Field population | Backend SDK document update | `updateDocumentFields()` in SampleController.php |
| Recipient addition | Backend SDK recipient management | `updateDocumentGroupRecipients()` in SampleController.php |
| Embedded sending creation | Backend SDK embedded sending | `createEmbeddedSendingUrl()` in SampleController.php |
| Status tracking | Backend SDK status API | `getDocumentGroupStatus()` in SampleController.php |
| Invite status tracking | Backend SDK invite API | `getInviteStatus()` and `getDocumentGroupSignersStatus()` in SampleController.php |
| Document download | Backend SDK download API | `downloadDocumentGroup()` and `downloadDocumentGroupFile()` in SampleController.php |

## Sequence of Function Calls

1. **Frontend Form Submission**
   - `initFormPage()` → Form validation
   - `fetch('/api/samples/EmbeddedSenderWithFormDGConstr')` → POST with action: 'prepare_dg'

2. **Backend Document Preparation**
   - `prepareDocumentGroup()` → Main orchestration
   - `createDocumentGroupFromTemplate()` → Create DG from template
   - `updateDocumentFields()` → Populate document fields
   - `updateDocumentGroupRecipients()` → Add recipients
   - `createEmbeddedSendingUrl()` → Generate embedded sending URL

3. **Frontend Embedded Sending**
   - Redirect to embedded sending URL from backend
   - User completes embedded sending in SignNow interface
   - Redirect back to status page with document_group_id

4. **Frontend Status Tracking**
   - `initStatusPage()` → Status page initialization
   - `fetch('/api/samples/EmbeddedSenderWithFormDGConstr')` → POST with action: 'invite-status'

5. **Backend Status Management**
   - `getInviteStatus()` → Handle invite status request
   - `getDocumentGroupSignersStatus()` → Get signers status for document group
   - `downloadDocumentGroup()` → Handle download request
   - `downloadDocumentGroupFile()` → Download merged PDF file

## Template Info

This sample requires a Document Group Template (DGT) to be configured in your SignNow account. The template should include:

- **Template Name**: Construction Project Contract
- **Template ID**: `625ae557229d48d78728c281f46d3d8494f235a4` (configured in SampleController.php)
- **Required Fields**: The template should include fields named "Name" for populating user data
- **Document Structure**: The template should contain the documents that need to be signed
- **Recipient Roles**: Template should include recipients with roles for signing

### Template Configuration
- Template must be accessible via the SignNow API
- Template should include appropriate signing fields
- Template should be configured for document group workflows
- Template should have recipients configured for signing

## Configuration

### Required Environment Variables
```bash
SIGNNOW_CLIENT_ID=your_client_id
SIGNNOW_CLIENT_SECRET=your_client_secret
SIGNNOW_USERNAME=your_username
SIGNNOW_PASSWORD=your_password
```

### Additional Configuration
- `signnow.api.signer_email` in config/signnow.php for the preparer email address

### SDK Classes Used
- `SignNow\Api\DocumentGroupTemplate\Request\DocumentGroupTemplatePost` - Create document group from template
- `SignNow\Api\DocumentField\Request\DocumentPrefillPut` - Update document fields
- `SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsPut` - Update document group recipients
- `SignNow\Api\EmbeddedSending\Request\DocumentGroupEmbeddedSendingLinkPost` - Create embedded sending
- `SignNow\Api\Auth\Request\TokenPost` - Generate limited scope tokens
- `SignNow\Api\DocumentGroup\Request\DocumentGroupGet` - Get document group status
- `SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost` - Download document group
- `SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet` - Get invite status for document group
- `SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet` - Get document group recipients

## Quick Start (TL;DR)

1. **Configure the sample**
   - Set up SignNow credentials in environment variables
   - Ensure template ID is correctly configured in SampleController.php

2. **Run the sample**
   - Navigate to the sample URL
   - Fill out the form with name and email
   - Click "Continue" to prepare the document

3. **Complete embedded sending**
   - Use the embedded sending interface to configure and send the document
   - Complete the sending process in SignNow interface

4. **Track completion**
   - Monitor the status page for document completion
   - View individual signer statuses and timestamps
   - Download the completed document when ready
   - Refresh status to get latest updates
   - Parent application is notified when workflow is complete

## SDK Integration Details

### Document Group Creation from Template
```php
// Create document group directly from Document Group Template
$documentGroupTemplatePost = new DocumentGroupTemplatePost(
    groupName: 'Construction Project Contract'
);
$documentGroupTemplatePost->withTemplateGroupId(self::DOCUMENT_GROUP_TEMPLATE_ID);

$response = $apiClient->send($documentGroupTemplatePost);
$documentGroupId = $response->getData()->getUniqueId();
```

### Field Population
```php
// Try to fill "CustomerName" field first, then "CustomerFN" as fallback
if (in_array('CustomerName', $existingFields)) {
    $fieldValues->add(new FieldValue(
        fieldName: 'CustomerName',
        prefilledText: $name
    ));
} elseif (in_array('CustomerFN', $existingFields)) {
    $fieldValues->add(new FieldValue(
        fieldName: 'CustomerFN',
        prefilledText: $name
    ));
}
```

### Recipient Update with Different Emails
```php
// Assign email based on recipient role/name
$emailToUse = $customerEmail; // Default to customer email
if ($recipientName === 'Prepare Contract') {
    $emailToUse = $preparerEmail; // Use config email for "Prepare Contract"
} elseif ($recipientName === 'Customer to Sign') {
    $emailToUse = $customerEmail; // Use form email for "Customer to Sign"
}

$updatedRecipients[] = new Recipient(
    name: $recipientName,
    email: $emailToUse,
    order: $recipient->getOrder(),
    documents: $requestDocumentCollection
);
```

### Embedded Sending
```php
// Create embedded sending URL with redirect to signing page
$redirectUrl = config('app.url') . '/samples/EmbeddedSenderWithFormDGConstr?' . 
    http_build_query([
        'page' => 'signing-page',
        'document_group_id' => $documentGroupId,
    ]);

$embeddedSendingRequest = new DocumentGroupEmbeddedSendingLinkPost(
    redirectUri: $redirectUrl,
    redirectTarget: 'self',
    linkExpiration: 15, // 15 minutes
    type: 'send-invite'
);
$embeddedSendingRequest->withDocumentGroupId($documentGroupId);
```



### Invite Status Tracking
```php
// Get invite status for document group
$inviteStatusRequest = (new GroupInviteGet())
    ->withDocumentGroupId($documentGroupId)
    ->withInviteId($inviteId);

$inviteStatusResponse = $apiClient->send($inviteStatusRequest);

// Process signer statuses
foreach ($inviteStatusResponse->getInvite()->getSteps() as $step) {
    foreach ($step->getActions() as $action) {
        $statuses[$action->getRoleName()] = $action->getStatus();
    }
}
```

### Document Download
```php
// Download merged PDF file
$downloadRequest = (new DownloadDocumentGroupPost(
    'merged',
    'no'
))->withDocumentGroupId($documentGroupId);

$response = $apiClient->send($downloadRequest);
$content = file_get_contents($response->getFile()->getRealPath());
unlink($response->getFile()->getRealPath());

return new Response(
    $content,
    200,
    [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="final_document_group.pdf"',
    ]
);
```

### Parent Application Notification
```javascript
// Notify parent application when workflow is complete
parent.postMessage({type: "SAMPLE_APP_FINISHED"}, location.origin);
```



## Error Handling

The sample includes comprehensive error handling for:
- Missing or invalid form data
- API authentication failures
- Document group creation errors
- Field update failures
- Recipient addition errors
- Embedded sending creation issues

- Status retrieval problems
- Invite status tracking errors
- Document download failures

All errors are logged and returned to the frontend with appropriate user-friendly messages. The frontend displays error messages to users and provides retry functionality for failed operations.

## Security Considerations

- All API calls use secure HTTPS connections
- Limited scope tokens are used for signing authentication
- Access tokens are managed securely
- User data is validated before processing
- Error messages don't expose sensitive information
- Embedded URLs are generated with proper security parameters

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

- Implement proper session management
- Add comprehensive input validation
- Implement proper error logging and monitoring
- Add rate limiting and security measures
- Test thoroughly with your specific use case
- Ensure compliance with data protection regulations
- Customize the workflow to match your business requirements

The sample demonstrates core SignNow API functionality but should be adapted and enhanced for production use. 