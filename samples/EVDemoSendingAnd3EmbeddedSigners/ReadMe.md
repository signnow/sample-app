# EVDemoSendingAnd3EmbeddedSigners — PHP Sample

## What this sample demonstrates

A **headless workflow** that clones a single SignNow template and walks three recipients through **sequential embedded signing** sessions:

1. **Agent** – template role **Contract Preparer**
2. **Signer 1** – template role **Recipient 1**
3. **Signer 2** – template role **Recipient 2**

After the third signer finishes, the application downloads the fully executed PDF.

---

## Step‑by‑step scenario

| Step | Action                                                                                                                                                                                         |
| ---- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1    | A user fills in a six‑field form and clicks **Continue**.                                                                                                                                      |
| 2    | The server clones the template (`TEMPLATE_ID = 34009a3d21b5468d86d886cd715658c453335c61`) and assigns the e‑mails to the three roles.                                                          |
| 3    | Only *Signer 1 Name* and *Signer 2 Name* are **prefilled**. The template has no *Agent Name* field, so that call is commented out in the code.                                                 |
| 4    | Embedded invites are created for all three roles with signing order 1 → 2 → 3.                                                                                                                 |
| 5    | The server returns the **Agent** signing link and redirects the front‑end to `?step=agent`. When the Agent clicks **Done** inside SignNow, the browser automatically lands on `?step=signer1`. |
| 6    | The same flow repeats for Signer 1 and then Signer 2.                                                                                                                                          |
| 7    | At `?step=finish` the front‑end requests `action=download` and receives the final PDF.                                                                                                         |

---

## Architecture overview

```
index.blade.php     – thin front‑end router (?step=)
SampleController.php – REST endpoint /api/samples/EVDemoSendingAnd3EmbeddedSigners
                      • GET  -> serves the Blade template
                      • POST -> start‑workflow / next‑signer / download / invite‑status
```

### Key controller methods

| Method                                 | Purpose                                                      |
| -------------------------------------- | ------------------------------------------------------------ |
| `createDocumentFromTemplate()`         | Clone the template                                           |
| `prefillFields()`                      | Write values into document fields                            |
| `createEmbeddedInvitesForAllSigners()` | Create embedded invites and return a map `roleId → inviteId` |
| `getEmbeddedInviteLink()`              | Retrieve an invite link and inject a `redirect_uri`          |
| `makeRedirectUrl()`                    | Build the next front‑end URL                                 |

---

## Technical flow

1. **start‑workflow**  – receives `agent_name/email`, `signer1_name/email`, `signer2_name/email`; creates document, pre‑fills names, returns Agent link.
2. **next‑signer** – each transition (Agent → Signer 1 or Signer 1 → Signer 2) fetches a new link, passing `roleName`.
3. **download** – after `step=finish` the client calls `action=download` and gets the PDF.
4. **invite‑status** – optional; can poll for real‑time invite status.

---

## Configuration

| Parameter           | Value / Description                                                                   |
| ------------------- | ------------------------------------------------------------------------------------- |
| **Template ID**     | `34009a3d21b5468d86d886cd715658c453335c61`                                            |
| **Roles**           | Contract Preparer · Recipient 1 · Recipient 2                                         |
| **SignNow PHP SDK** | Requires environment variables `SN_CLIENT_ID`, `SN_CLIENT_SECRET`, `SN_REDIRECT_URI`. |

---

## Running the sample

```bash
composer install
cp .env.example .env      # add your SignNow credentials
php artisan serve
# open http://127.0.0.1:8000/samples/EVDemoSendingAnd3EmbeddedSigners
```

> **NOTE:** The sample is for demonstration only; add proper validation, error handling and security measures before using it in production.
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

## License / Disclaimer

This sample is provided "as is" without warranty. Audit and harden the code before deploying in a production environment.
