# Prefill and One Click Sending Agreement

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement a **Prefill and One-Click Send** workflow:

1. Collecting a signer name and email via a web form.
2. Cloning a document template and pre-filling a name field.
3. Sending a field invite directly to the signer's email (one-click send — no embedded sender UI).
4. Monitoring invitation statuses.
5. Downloading the final signed document.

## Technical Flow

1. **GET Request Handling**
    - No `page` parameter: renders the initial form with name and email fields.
    - `?page=status-container`: renders the status monitoring view.

2. **Form Submission**
    - The backend clones the predefined template, pre-fills the `Name` field, resolves the signer role ID, and sends an email invite via `SendInvitePost`.
    - The frontend is redirected to the status page with the document ID.

3. **Status Polling**
    - Frontend polls `/api/samples/PrefillAndOneClickSendingAgreement` with `action: 'invite-status'`.
    - Backend fetches field invites via `DocumentGet` and returns JSON statuses.

4. **Document Download**
    - On click, sends `action: 'download'` to fetch the flattened PDF (`DocumentDownloadGet`) and triggers a browser download.

## Configuration Notes

- **Template ID**: Defined as `TEMPLATE_ID` in the controller.
- **Role Name**: `Recipient 1` — must match the role defined in the template.
- **SignNow SDK**: Uses the official SignNow PHP SDK; credentials loaded from the `.env` configuration.

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
Example: @signnow Generate a PHP code example for sending a one-click invite with prefilled fields.

4\. Modify the generated code to match your app's requirements—adjust parameters, headers, and workflows as needed.

### **Troubleshooting**
**The extension doesn't provide code examples for the SignNow API**

Make sure you're using `@signnow` in the Copilot chat and that the extension is installed and authorized.
____

## Disclaimer

This sample application is provided **as-is** for demonstration purposes. It is not production-ready and should be reviewed for proper error handling, input validation, security best practices, and customized to fit real-world business requirements.
