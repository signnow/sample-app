# Embedded Signer Sample: Veterinary Clinic Intake Form

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement an **Embedded Signer** workflow for a veterinary clinic. In this flow, the user completes and signs a pre-defined "Veterinary Clinic Intake Form" within the application and is then redirected to a confirmation screen where they can download the completed document.

This specific example mimics a real-world scenario in the veterinary services sector where an intake form must be completed and signed digitally.

## Scenario: Embedded Signer Without Form

### Step-by-step Interaction:

1. **Open Embedded Signer**
   - The user accesses the application and is redirected to the embedded signing session for the "Veterinary Clinic Intake Form."
   - The app initiates the process by cloning a predefined template to create a document instance that can be signed.

2. **Complete the Document**
   - The user fills out the necessary fields in the "Veterinary Clinic Intake Form" using the embedded interface.
   - The interface allows the user to complete all required information directly within the app.

3. **Sign the Document**
   - After completing the form, the user signs the document using the embedded signing feature.
   - The signing process is integrated seamlessly into the app, ensuring a smooth user experience.

4. **Redirect to Finish Screen**
   - Once the document is signed, the user is redirected to a "Finish" page.
   - This page confirms that the signing process is complete and provides further instructions.

5. **Download the Completed Document**
   - On the "Finish" page, the user is presented with an option to download the completed and signed PDF document.
   - The user clicks the "Download" button, which triggers a request to fetch the signed document.

6. **Document Delivery**
   - The app processes the download request and retrieves the signed PDF.
   - The completed document is then sent to the user, allowing them to save it locally.

## Technical Flow

1. **GET Request Initiation**
   - The user accesses the app, which checks for a `page` query parameter.
   - If `page=finish`, the app displays the "Finish" page with a download option.
   - Otherwise, the app initiates an embedded signing session:
     - Clones the "Veterinary Clinic Intake Form" template.
     - Creates an embedded invite for the user.
     - Generates a secure signing link and redirects the user to it.

2. **Template Cloning**
   - The app clones the predefined template to create a signable document instance.
   - This step is necessary as templates cannot be signed directly.

3. **Embedded Invite Generation**
   - The app retrieves the signer's role ID from the cloned document.
   - An embedded invite is created and assigned to the user's email.

4. **Signing Link Generation**
   - A signing link is generated using the SignNow API.
   - The user is redirected to this link to complete and sign the document.

5. **POST Request for Download**
   - After signing, the user is redirected to the "Finish" page.
   - Clicking the download button sends a `POST` request with the `document_id`.
   - The app fetches the signed PDF and delivers it to the user for download.

## Notes
- The template ID used in this app: `b6797f3437db4c818256560e4f68143cb99c7bc9`.
- The template is a "Veterinary Clinic Intake Form (Consumer Services)."
- Signer's email is configured within the app settings.
- All API requests are executed via the official SignNow SDK using secure credentials.

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
This flow is designed for demonstration purposes. The embedded signing process relies on a static template and should be customized for production use.
