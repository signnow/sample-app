# Embedded Signer Sample Application: Credit Loan Agreement with Multiple Recipients

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement an **Embedded Signer With Form** workflow that allows:

1. Collecting user data via a web form.
2. Configuring multiple recipients (including sender as signer).
3. Sending invitations for embedded signing.
4. Monitoring invitation statuses.
5. Downloading the final signed document.

The flow is suitable for business scenarios where a form-based agreement (e.g., credit loan) requires signatures from multiple parties.

## Scenario: Embedded Signer With Form and Multiple Recipients

### Step-by-step:

1. **Form Input**
    - Display a form asking for **Name** (required) and **Email** (optional).
    - User fills the form and clicks **Continue** to proceed.

2. **Custom Send Invite Page**
    - Pre-configured with **3 recipients**:
        - Recipient 1: **Sender as Signer** (checkbox checked and disabled).
        - Recipient 2: Email pre-filled from the form's Email field.
        - Recipient 3: Blank email field.
    - All recipient emails are required.
    - User clicks **Send Invite** to generate an embedded signing link for Recipient 1 and move to status monitoring.

3. **Embedded Signing Session**
    - An embedded signing link is created and opened for Recipient 1 within the application.

4. **Send Status Page**
    - The app polls the backend every 3 seconds to retrieve current email statuses for all recipients.
    - Displays a list of recipients, their status timestamps, and a badge indicating status (e.g., sent, viewed, signed).
    - Includes a **Refresh** button (automated polling) and a **Finish Demo** button to proceed.

5. **Finish Page**
    - A confirmation screen indicating the process is complete.
    - Option to start a new session or exit the demo.

## Technical Flow

1. **GET Request Handling**
    - No `page` parameter: renders the initial form (`form-container`).
    - `?page=download-with-status`: renders the status monitoring view (`download-with-status`).

2. **Form Submission & Redirect**
    - On form submit, JavaScript captures form values and redirects to the custom send-invite controller endpoint with query parameters.

3. **Template Cloning & Invite Creation**
    - Backend clones a document from the predefined template ID.
    - Pre-fills form fields and sets up recipient invites (including sender as signer).
    - Generates an embedded sending link via `DocumentEmbeddedSendingLinkPost` with a redirect back to status monitoring.

4. **Status Polling & Rendering**
    - Frontend polls `/api/samples/EmbeddedSenderWithFormCreditLoanAgreement` with `action: 'invite-status'`.
    - Backend fetches invites via `DocumentGet`, extracts email_statuses, formats timestamps, and returns JSON.
    - Frontend renders status list and handles download actions.

5. **Document Download**
    - When a recipient completes signing, the frontend displays **Download Document** buttons.
    - On click, sends `action: 'download'` to fetch the flattened PDF (`DocumentDownloadGet`) and triggers browser download.

## Configuration Notes

- **Template ID**: Defined in the controller (e.g., `de45a9a2a6014c2c8ac0a4d9057b17a2108e77e7`).
- **Redirect URL**: Configured via `config('app.url')`, appended with `?page=download-with-status&document_id={document_id}`.
- **SignNow SDK**: Uses the official SignNow PHP SDK; credentials loaded from environment settings.

## Disclaimer

This sample application is provided **as-is** for demonstration purposes. It is not production-ready and should be reviewed for proper error handling, input validation, security best practices, and customized to fit real-world business requirements.
