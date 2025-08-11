<!doctype html>
<html class="no-js" lang="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EVDemoSendingAnd3EmbededSigners</title>
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <link rel="stylesheet" href="/css/styles.css">
    <meta name="description" content="SignNow EVDemoSendingAnd3EmbededSigners">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icon.png">
    <meta name="theme-color" content="#fafafa">
</head>

<body>

<div class="header">
    <img src="/img/sign-now.png" alt="SignNow Logo">
</div>

<!-- 1) Initial Form Container (step=start) -->
<div id="form-container" style="display:none">
    <h4 class="mb-3">Enter Agent and Signers Information</h4>
    <form id="startWorkflowForm" action="#" method="post">
        <div class="sn-input-group mb-2">
            <label for="agent_name">Agent Name <span class="text-danger">*</span></label>
            <input type="text" id="agent_name" name="agent_name" required placeholder="e.g. Jane Doe">
        </div>
        <div class="sn-input-group mb-2">
            <label for="agent_email">Agent Email <span class="text-danger">*</span></label>
            <input type="email" id="agent_email" name="agent_email" required placeholder="e.g. agent@example.com">
        </div>

        <div class="sn-input-group mb-2">
            <label for="signer1_name">Signer 1 Name <span class="text-danger">*</span></label>
            <input type="text" id="signer1_name" name="signer1_name" required placeholder="e.g. John Smith">
        </div>
        <div class="sn-input-group mb-2">
            <label for="signer1_email">Signer 1 Email <span class="text-danger">*</span></label>
            <input type="email" id="signer1_email" name="signer1_email" required placeholder="e.g. signer1@example.com">
        </div>

        <div class="sn-input-group mb-2">
            <label for="signer2_name">Signer 2 Name <span class="text-danger">*</span></label>
            <input type="text" id="signer2_name" name="signer2_name" required placeholder="e.g. Sarah Brown">
        </div>
        <div class="sn-input-group mb-4">
            <label for="signer2_email">Signer 2 Email <span class="text-danger">*</span></label>
            <input type="email" id="signer2_email" name="signer2_email" required placeholder="e.g. signer2@example.com">
        </div>

        <button type="submit" class="button-primary" id="startWorkflowBtn">Continue</button>
        <button class="btn btn-dark d-none" id="loadingBtnStart" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Loading...
        </button>
    </form>
</div>

<!-- 2) Signing Step Container: Prompt user to open embedded link (Agent, Signer1, Signer2) -->
<div id="signing-container" style="display:none">
    <div class="thank-you-message text-center">
        <h4 id="signing-title" class="mb-3"></h4>
        <p id="signing-instructions" class="mb-3"></p>
        <button class="btn btn-dark d-none" id="loadingBtnSigning" type="button" disabled>
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Loading signing session...
        </button>
    </div>
</div>

<!-- 3) Final Step Container: Download -->
<div id="finish-container" style="display:none">
    <div class="thank-you-message text-center">
        <img src="/img/doc-completed.png" alt="signNow" class="mb-3" />
        <h4>You've filled out and signed a document</h4>
        <p class="mb-4">A copy was also sent to your inbox and saved to your signNow account.</p>
        <button id="downloadBtn" class="button-secondary">Download Document</button>
    </div>
</div>

<div class="copyright gray--700 mt-3">Copyright (c) 2025 airSlate, Inc., SignNow API Sample Application v3.0</div>

<script>
/* ---------------------------------------------------------------------------
   Minimal client-side routing based on ?step= (and possibly ?document_id=).
   We'll manage three main steps:
     1) step=undefined or "start" => Show the 6-field form
     2) step=signer1 / step=signer2 / step=agent => Show the signing prompt,
        then open the embedded link (fetched from the server) for that role
     3) step=finish => Show the final download container
   --------------------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', main);

async function main() {
    // Map step -> a handler function
    const pagesMap = {
        start   : showStartForm,
        agent   : showSigningPrompt,   // 1st signer (Agent)
        signer1 : showSigningPrompt,   // 2nd signer
        signer2 : showSigningPrompt,   // 3rd signer
        finish  : showFinishPage
    };

    // Default step: "start"
    const urlParams = new URLSearchParams(window.location.search);
    const step      = urlParams.get('step') || 'start';

    hideAllContainers();
    if (pagesMap[step]) {
        await pagesMap[step](step);
    } else {
        await showStartForm(); // fallback
    }
}

/** Hide all major containers */
function hideAllContainers() {
    document.querySelectorAll('#form-container, #signing-container, #finish-container')
        .forEach(el => el.style.display = 'none');
}

/** STEP 1: Show the initial form for capturing Agent & Signer details */
function showStartForm() {
    const formContainer     = document.getElementById('form-container');
    const startWorkflowForm = document.getElementById('startWorkflowForm');
    const startBtn          = document.getElementById('startWorkflowBtn');
    const loadingBtn        = document.getElementById('loadingBtnStart');

    formContainer.style.display = 'block';

    // On form submit => POST to "start-workflow"
    startWorkflowForm.addEventListener('submit', async (evt) => {
        evt.preventDefault();
        startBtn.classList.add('d-none');
        loadingBtn.classList.remove('d-none');

        const agent_name     = document.getElementById('agent_name').value;
        const agent_email    = document.getElementById('agent_email').value;
        const signer1_name   = document.getElementById('signer1_name').value;
        const signer1_email  = document.getElementById('signer1_email').value;
        const signer2_name   = document.getElementById('signer2_name').value;
        const signer2_email  = document.getElementById('signer2_email').value;

        try {
            const response = await fetch('/api/samples/EVDemoSendingAnd3EmbededSigners', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    action         : 'start-workflow',
                    agent_name,
                    agent_email,
                    signer1_name,
                    signer1_email,
                    signer2_name,
                    signer2_email
                })
            });

            if (!response.ok) throw new Error('Server error when starting workflow.');

            const data = await response.json();
            if (!data.document_id || !data.embedded_link) {
                throw new Error('Missing "document_id" or "embedded_link" in response.');
            }

            // Next step is: agent signing
            // We rely on the server's redirectUrl set to step=signer1 after Agent finishes.
            const nextUrl = new URL(window.location.href);
            nextUrl.searchParams.set('step', 'agent');
            nextUrl.searchParams.set('document_id', data.document_id);
            // We store the Agent's link in the URL too
            nextUrl.searchParams.set('embedded_link', data.embedded_link);

            window.location.href = nextUrl.toString();
        } catch (err) {
            alert(err.message || 'Could not start workflow.');
            startBtn.classList.remove('d-none');
            loadingBtn.classList.add('d-none');
        }
    });
}

/** STEP 2: Show the signing prompt for whichever role is next (agent, signer1, signer2) */
async function showSigningPrompt(step) {
    const signingContainer   = document.getElementById('signing-container');
    const signingTitle       = document.getElementById('signing-title');
    const signingInstructions= document.getElementById('signing-instructions');
    const loadingBtnSigning  = document.getElementById('loadingBtnSigning');

    signingContainer.style.display = 'block';

    // Identify which role we are presenting
    // (In the template, roles are: "Contract Preparer" => agent, "Recipient 1" => signer1, "Recipient 2" => signer2)
    const roleMap = {
        agent   : { displayName:'Agent', roleName:'Contract Preparer' },
        signer1 : { displayName:'Signer 1', roleName:'Recipient 1' },
        signer2 : { displayName:'Signer 2', roleName:'Recipient 2' }
    };

    const urlParams = new URLSearchParams(window.location.search);
    const documentId     = urlParams.get('document_id');
    const embedded_link  = urlParams.get('embedded_link');
    const thisRole       = roleMap[step];

    if (!documentId) {
        signingTitle.textContent        = 'Document ID Not Found';
        signingInstructions.textContent = 'Cannot start signing. Missing document reference.';
        return;
    }

    // Label the UI
    signingTitle.textContent        = `Start Signing as ${thisRole.displayName}`;
    signingInstructions.textContent = ``;

    // For "agent" step, we already have embedded_link from "start-workflow" call.
    // For "signer1" or "signer2", we must fetch "next-signer" from the server to get a new link.
    let signingLink = embedded_link || '';
    if (!signingLink && (step === 'signer1' || step === 'signer2')) {
        loadingBtnSigning.classList.remove('d-none');

        // We need to call "next-signer" to get the link
        try {
            const response = await fetch('/api/samples/EVDemoSendingAnd3EmbededSigners', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    action     : 'next-signer',
                    document_id: documentId,
                    roleName   : thisRole.roleName,
                    // The server can set the redirect for the next role or finish
                })
            });
            if (!response.ok) throw new Error('Server failed to create next-signer link');
            const data = await response.json();
            if (!data.embedded_link) {
                throw new Error('Missing embedded_link in next-signer response');
            }
            signingLink = data.embedded_link;
        } catch (err) {
            signingTitle.textContent        = 'Error Getting Signing Link';
            signingInstructions.textContent = err.message;
            loadingBtnSigning.classList.add('d-none');
        }

        loadingBtnSigning.classList.add('d-none');
    }

    // If the link is already obtained (agent step or successful fetch), open it immediately.
    const openLink = () => {
        if (signingLink) {
            window.location.href = signingLink;
        } else {
            alert('No embedded signing link available for this role.');
        }
    };

    openLink();
}

/** STEP 3: Final page to download the completed document */
function showFinishPage() {
    const finishContainer = document.getElementById('finish-container');
    finishContainer.style.display = 'block';

    const urlParams     = new URLSearchParams(window.location.search);
    const documentId    = urlParams.get('document_id');
    const downloadBtn   = document.getElementById('downloadBtn');

    // Уведомляем parent (если форма открыта во фрейме в примере) о завершении
    try { parent.postMessage({type: "SAMPLE_APP_FINISHED"}, window.origin); } catch(e) {}

    downloadBtn.onclick = async () => {
        if (!documentId) {
            alert('No document ID found, unable to download.');
            return;
        }
        try {
            const response = await fetch('/api/samples/EVDemoSendingAnd3EmbededSigners', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({
                    action     : 'download',
                    document_id: documentId
                })
            });
            if (!response.ok) throw new Error('Download request failed.');

            const blob = await response.blob();
            const url  = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href     = url;
            link.download = 'signed-document.pdf';
            link.click();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            alert(`Document download error: ${error.message}`);
        }
    };
}
</script>
</body>
</html>
