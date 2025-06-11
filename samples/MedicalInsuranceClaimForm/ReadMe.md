# Embedded Signer Sample Application: Medical Insurance Claim Form

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement an **Embedded Signer With Form** workflow. In this flow, the user first fills out a form in the app (e.g., name and email), then is redirected into an embedded signing session. After signing, the user lands on a confirmation page where they can download the completed document.

This example mimics a real-world healthcare scenario in which a **Medical Insurance Claim Form** must be pre-filled, signed, and downloaded.
## Scenario: Embedded Signer With Form

### Step-by-step:
1. **User opens the form** — A simple web form is displayed asking for the signer's name (required) and email (optional).
2. **User submits the form** —  A document is cloned from a template, and fields are pre-filled with the form values.
3. **Embedded signing session** — An embedded signing link is created and opened inside the app.
4. **Redirect to the completion screen** — After signing, the user is redirected to a "Thank You" page.
5. **Download option** — The user can download the signed PDF.

## Technical Flow

1. **GET Request Initiation**
    - If the page is accessed without a `page` parameter, it shows the initial input form.
    - If the page is accessed with `?page=download-container`, the download screen is shown.

2. **Template Cloning**
    - A **document template** cannot be sent for signing directly.
    - It must be first **cloned** to create a **signable document**.
    - The document is cloned from a **preloaded template** in our **demo SignNow account**.

3. **Field Prefilling**
    - The new document is pre-filled using values from the submitted form (`full_name`, `email`).
    - These values populate fields in the cloned document before signing begins.

4. **Embedded Invite Creation**
    - To generate an embedded signing link, the app does the following:
        - Fetches the role ID from the cloned document.
        - Creates an **embedded invite** specifically (not a regular invite).

5. **Signing Link Generation**
    - An embedded signing link is generated.
    - A redirect URL is appended to bring the user to the finish page after signing.

6. **Document Download**
    - On the "Thank You" page, the user can click a button to download the signed PDF.
    - A `POST` request sends the `document_id` to the server.
    - The app fetches the signed PDF and triggers the download.

## Notes
- The template ID used in this demo: `60d8e92f12004fda8985d4574237507e6407530d`
- The template is a "Medical Insurance Claim Form".
- The signer's email is pulled from config: `config('signnow.api.signer_email')`
- All API operations are performed using the official SignNow PHP SDK and `.env` credentials.

## Disclaimer
This sample application is for **demonstration purposes only**. The application uses a static template hosted in our demo SignNow account. This flow should be adapted and properly secured before being used in production.
