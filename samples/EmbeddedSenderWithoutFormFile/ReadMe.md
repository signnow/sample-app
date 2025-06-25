# Embedded Sender Sample Application: Embedded Sender Without Form File

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement an **Embedded Sending Workflow**. In this flow, the user:

1. Opens an embedded editor to add recipients and fillable fields.
2. Sends invitations to the added recipients.
3. Monitors the send status.
4. Completes the process and finishes on a final confirmation page.

This flow mimics a scenario in which a document is prepared and sent programmatically for signing, then tracked until completion.

## Scenario: Embedded Sender Without Form File

### Step-by-step:

1. **Embedded Sender > Editor**
    - The user is redirected into an embedded editor where they can **add recipient(s)** and **place fillable fields** on the document.
    - The **Continue** button appears once recipients and fields are defined.

2. **Embedded Sender > Send Invite**
    - The user enters the **required** recipient email(s).
    - By clicking **Send Invite**, an invitation is sent to **Recipient 1** and the app proceeds to Step 3.

3. **Send Status Page**
    - The app displays a list of recipients and their current **email status** (e.g., sent, viewed, signed).
    - The user can click **Refresh** to poll the API for updated statuses.
    - A **Finish Demo** button allows proceeding to the final step.

4. **Finish Page**
    - A confirmation screen indicates that the process is complete.
    - The user can **start a new session** or exit.

## Technical Flow

1. **GET Request Handling**
    - If accessed without a `page` parameter, the controller triggers the **embedded sending** flow by cloning a document from a template and generating an embedded editor link.
    - If accessed with `?page=download-with-status`, the send status page is rendered.

2. **Template Cloning**
    - Only documents (not templates) can be sent.
    - The app clones a document from a predefined template (`TEMPLATE_ID`) using the SignNow PHP SDK.

3. **Embedded Sending Link Generation**
    - An embedded sending link is created via `DocumentEmbeddedSendingLinkPost` with a redirect URL pointing back to the send status page.
    - The user is redirected into the SignNow embedded editor.

4. **Status Polling and Rendering**
    - The frontend polls the backend endpoint (`action: 'invite-status'`) every 3 seconds.
    - The backend fetches recipient invites via `DocumentGet` and returns their email statuses.
    - The frontend updates the displayed status list and provides **Download Document** buttons for each recipient once the document is signed.

5. **Document Download**
    - When the user clicks **Download Document**, the app sends a `download` action to the backend.
    - The backend retrieves the signed PDF via `DocumentDownloadGet` and returns it as an attachment.

## Configuration Notes

- **Template ID**: `76713f00c106425ea8b673c49fd94c0145643c34`
- **Redirect URL**: Configured via `config('app.url')`, appended with `?page=download-with-status&document_id={document_id}`.
- **SignNow SDK**: Uses the official SignNow PHP SDK; credentials taken from environment variables.

## Disclaimer

This sample is provided **as-is** for demonstration purposes. It should be reviewed and **hardened** before use in production environments, including proper error handling, input validation, and security best practices.
