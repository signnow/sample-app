# Embedded Signer Sample Application: EmbeddedSignerWithFormInsurance

## Use Case Overview

This sample application demonstrates how to implement an **Embedded Signer With Form** workflow using the SignNow API. The app guides users through a seamless process of filling out, signing, and downloading a **Medical Insurance Claim Form**. This workflow is designed to enhance user experience by pre-filling user information and providing an intuitive interface for document signing and downloading.

## Scenario: Embedded Signer With Form

### Step-by-step:

1. **User opens the form** — The application displays a simple web form requesting the user's name (required) and email (optional).
2. **User submits the form** — Upon submission, a document is cloned from a preloaded template, and fields are pre-filled with the user's input.
3. **Embedded signing session** — An embedded signing link is generated and opened within the app for the user to sign the document.
4. **Redirect to the completion screen** — After signing, the user is redirected to a "Thank You" page.
5. **Download option** — The user is given the option to download the signed PDF document.

## Technical Flow

1. **GET Request Initiation**
    - The initial form is displayed if the page is accessed without a `page` parameter.
    - If accessed with `?page=download-container`, the download screen is shown.

2. **Template Cloning**
    - The document template cannot be sent for signing directly.
    - It must first be **cloned** to create a **signable document**.
    - The document is cloned from a **preloaded template** in our **demo SignNow account** using TemplateId: `'c78e902aa6834af6ba92e8a6f92b603108e1bbbb'`.

3. **Field Prefilling**
    - The cloned document is pre-filled with values from the submitted form (`Name`, `Email`).
    - These values populate the fields in the document before the signing session begins.

4. **Embedded Invite Creation**
    - To generate an embedded signing link, the app performs the following:
        - Fetches the role ID from the cloned document.
        - Creates an **embedded invite** specifically for the signing session.

5. **Signing Link Generation**
    - An embedded signing link is generated.
    - A redirect URL is appended to ensure the user is taken to the finish page after signing.

6. **Document Download**
    - On the "Thank You" page, the user can click a button to download the signed PDF.
    - A `POST` request is sent with the `document_id` to the server.
    - The app fetches the signed PDF and initiates the download.

## Technical Considerations

- **Template Cloning**: Each user interaction results in a unique, signable document cloned from a static template.
- **Field Prefilling**: Reduces manual input by using user-provided data to pre-fill the document.
- **Embedded Invite**: Ensures a seamless signing process within the application.
- **Security and Adaptation**: This flow is designed for demonstration purposes and should be adapted and secured appropriately for production use.

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

This sample application is for **demonstration purposes only**. It uses a static template hosted in our demo SignNow account. The flow should be adapted and properly secured before being deployed in a live environment.