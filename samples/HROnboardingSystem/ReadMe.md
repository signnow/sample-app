# HR Onboarding System Sample Application: Embedded Signing with a Document Group

## Use Case Overview

This sample demonstrates how to use the SignNow PHP SDK to implement an **Embedded Signing** flow involving multiple users and documents. It represents a typical HR onboarding scenario, where new employees need to sign several documents in a specific order.

## Scenario: Employee Onboarding with Embedded Invite

### Step-by-step:
1. **User opens the onboarding form** — They input Employee Name, Employee Email, and HR Manager Email.
2. **User selects one or more onboarding templates** — e.g., NDA, Employment Contract, I-9 Form.
3. **Application clones selected templates** - Each template becomes a signable document.
4. **Fields are pre-filled** — The app inserts the employee’s name and email into specific fields.
5. **Documents are grouped** — All documents are added to a SignNow Document Group.
6. **Embedded signing invite is created** — The signing order is set: first the HR manager, then the employee.
7. **Signing link is generated** — The app returns a secure signing URL for the first signer (HR).
8. **Frontend polls for status** — The app checks whether the signing is complete.
9. **Signed document group is available for download** — Once all signers finish.

## Technical Flow

1. **GET Request to `handleGet()`**
    - Renders the HTML form UI for entering employee and HR data.

2. **POST Request to `handlePost()`**
    - Based on the `action` parameter, routes to different logic:
        - `create-embedded-invite`
            - Calls `createDocumentGroup()`
                - Clones templates via `createDocumentFromTemplate()`
                - Prefills data using `prefillFields()`
                - Creates a document group using `createDocumentGroupFromDocuments()`
            - Calls `createEmbeddedInvite()` to assign signers and roles
            - Calls `getEmbeddedInviteLink()` to generate a signing link for the first recipient
        - `invite-status`
            - Calls `getDocumentGroupInviteStatus()` to get signing status
        - _default (no action or download)_
            - Calls `downloadDocumentGroup()` to return merged, signed PDF file

## Sequence of PHP Function Calls

1. **handleGet()** — renders the form on page load
2. **handlePost()** — routes based on user interaction:
    - If `create-embedded-invite`:
        - createDocumentGroup()
            - createDocumentFromTemplate()
            - prefillFields()
            - createDocumentGroupFromDocuments()
        - createEmbeddedInvite()
            - getDocumentGroup()
        - getEmbeddedInviteLink()
    - If `invite-status`:
        - getDocumentGroupInviteStatus()
            - getDocumentGroup()
    - If downloading PDF:
        - downloadDocumentGroup()

## Template Info
- Templates are pre-uploaded to our **demo SignNow account**.
- Each must be cloned into a live document before use.
- Field names and signer roles (e.g., "Employee", "Employer") are matched programmatically.

## Disclaimer
This sample is for **demonstration purposes only** and relies on static templates stored in a **demo SignNow account**. It is not intended for production use without adjustments for authentication, document management, error handling, and security.

